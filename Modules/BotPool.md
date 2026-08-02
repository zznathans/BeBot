# BotPool

Lets a pool of bot instances share outbound work: any module calls
`dispatch()` to queue a message instead of sending it directly, and
whichever pool member is free picks it up and sends it via its own AoChat
connection.

This is a genuine **competing-consumers work queue** - each message is
handled by exactly one bot, not broadcast to all of them. Every slave bot
joins the *same* Redis consumer group, so Redis itself distributes entries
across whichever consumers are currently reading, rather than every bot
seeing every message. If you want the same message delivered to *every*
bot (e.g. relaying guild/org chat between bots), use
[`Relay.php`](Relay.php) instead - it's a different tool for a different
job. See also [`TimerRelay.php`](TimerRelay.php), which rides on
`Relay.php`'s own tell/pgroup transport rather than BotPool's queue.

## How it works

- Publishing (`dispatch()`) does an `XADD` onto a shared Redis Stream.
- Consuming happens on every `cron 2sec` tick (not a blocking read - BeBot
  is a single cooperative event loop, so a blocking `XREADGROUP` would
  starve AO chat and timers). Each slave calls `XREADGROUP` under the
  shared consumer group; Redis hands back only entries not already claimed
  by another consumer in that group.
- A received entry is sent via the bot's own `send_gc()`/`send_pgroup()`/
  `send_tell()` (whichever `channelType` the message specifies), then
  `XACK`'d.
- The queue lives entirely in Redis - the handoff between bots never
  touches AO chat itself, unlike `Relay.php`'s tell/pgroup-based relaying.

## Requirements

- `bebot.redis.enabled` must be `true` for the shared Redis instance every
  participating bot connects to (see `bebot-helm`'s chart values). A bot
  with no/unreachable Redis simply can't publish or consume - same
  fail-soft contract every other `Redis_Client` caller has, it doesn't
  error, it just silently does nothing.
- Every bot that should compete for the same work must be configured with
  the **same** `BotPool.StreamKey` and `BotPool.GroupName` (defaults below
  are fine to leave as-is unless you're running more than one independent
  pool against the same Redis instance).

## Settings

Set via `!set botpool <Setting> <value>` on each bot, same as any other
module's settings.

| Setting | Default | Meaning |
|---|---|---|
| `BotPool.Role` | `slave` | `main` publishes only, `slave` consumes only, `both` does either. |
| `BotPool.StreamKey` | `botpool:dispatch` | The shared Redis stream key. Must match across the whole pool. |
| `BotPool.GroupName` | `botpool-workers` | The shared consumer group name. Must match across every slave, since that's what makes them compete for the same work instead of each one seeing every message. |

## Example setup

Two bots (`Tickr`, `Tickr1`) sharing outbound work, a third (`Beuroman`)
only ever publishing:

```
# On Beuroman:
!set botpool Role main

# On Tickr and Tickr1 (identical on both):
!set botpool Role slave
```

Leave `StreamKey`/`GroupName` at their defaults unless you're running more
than one pool against the same Redis instance.

## Using it from another module

```php
$this->bot->core("botpool")->dispatch($channelType, $channelArg, $msg, $color = null);
```

- `$channelType` - `'gc'`, `'pgroup'`, or `'tell'`.
- `$channelArg` - the pgroup name / tell recipient; `null` for `'gc'`.
- `$msg` - the message text.
- `$color` - optional hex color, same as `send_pgroup()`/`send_tell()`'s
  own `$color` argument.

`dispatch()` is fire-and-forget from the caller's perspective - the
stream's durability means a slave that's briefly down still picks the
message up once it reconnects and reads, there's nothing for the caller to
retry or check.

## Known limitation

If a consumer reads an entry and crashes before acking it, that entry
stays pending in the group (visible via `XPENDING`) rather than being
lost - but nothing currently reclaims (`XCLAIM`) a long-pending entry from
a dead consumer. A message stranded that way needs a manual look (or a
future `XCLAIM`-based reclaim job) rather than being handled automatically
today.
