<?php
use PHPUnit\Framework\TestCase;

/*
Regression coverage for the Market-Poll/Market-AutoTrack internal timer setup in
Market::__construct(). These two timers used to only be added when
Timer_Core::list_timed_events() didn't already show one - a guard that's only as good as
that one lookup on that one boot. If it ever missed an existing row (a DB hiccup, or a
pre-guard version of this code), the module kept adding another same-named timer forever,
each firing independently on its own schedule and multiplying the effective frequency of
both the price-history poll and the ao-stonks.com auto-track resync - see the "!market
autotrack running way more often than configured" investigation this covers.

The fix makes boot self-healing instead of add-once-if-missing: unconditionally delete any
existing Market-Poll/Market-AutoTrack rows, then add exactly one fresh one of each. These
tests pin that shape so a regression back to the old guarded-add pattern is caught here
rather than in production Loki logs.
*/
class MarketTest extends TestCase
{
    /** Constructing Market registers exactly one Market-Poll and one Market-AutoTrack timer. */
    public function testConstructorAddsExactlyOnePollAndOneAutoTrackTimer()
    {
        $bot = new FakeMarketBot();
        new Market($bot);

        $names = array();
        foreach ($bot->timer->addedTimers as $timer) {
            $names[] = $timer['name'];
        }
        $this->assertSame(array('Market-Poll', 'Market-AutoTrack'), $names);
    }


    /** The constructor issues a cleanup DELETE for both timer names before re-adding them. */
    public function testConstructorDeletesAnyExistingCopiesOfBothTimersFirst()
    {
        $bot = new FakeMarketBot();
        new Market($bot);

        $deletes = array_values(array_filter($bot->db->queries, function ($sql) {
            return strpos($sql, 'DELETE FROM #___timer') === 0;
        }));

        $this->assertCount(1, $deletes, 'expected exactly one cleanup DELETE against the timer table');
        $this->assertStringContainsString("owner = 'Market'", $deletes[0]);
        $this->assertStringContainsString("channel = 'internal'", $deletes[0]);
        $this->assertStringContainsString('Market-Poll', $deletes[0]);
        $this->assertStringContainsString('Market-AutoTrack', $deletes[0]);
    }


    /**
     * Repeated construction (simulating restarts against the same persistent timer table)
     * never accumulates duplicate timers - the cleanup DELETE fires on every boot, not just
     * the first.
     */
    public function testConstructorIsSelfHealingAcrossRepeatedBoots()
    {
        $bootCount = 3;
        $deleteCountPerBoot = array();

        for ($i = 0; $i < $bootCount; $i++) {
            $bot = new FakeMarketBot();
            new Market($bot);
            $deletes = array_filter($bot->db->queries, function ($sql) {
                return strpos($sql, 'DELETE FROM #___timer') === 0;
            });
            $deleteCountPerBoot[] = count($deletes);
            $this->assertCount(2, $bot->timer->addedTimers, "boot #$i should add exactly two timers");
        }

        $this->assertSame(array(1, 1, 1), $deleteCountPerBoot);
    }


    /** Timer durations/repeat intervals reflect the PollIntervalMinutes/AutoTrackIntervalMinutes settings. */
    public function testPollAndAutoTrackIntervalsComeFromSettings()
    {
        $bot = new FakeMarketBot();
        $bot->settings->set('Market', 'PollIntervalMinutes', 15);
        $bot->settings->set('Market', 'AutoTrackIntervalMinutes', 90);

        new Market($bot);

        $byName = array();
        foreach ($bot->timer->addedTimers as $timer) {
            $byName[$timer['name']] = $timer;
        }

        $this->assertSame(15 * 60, $byName['Market-Poll']['duration']);
        $this->assertSame(15 * 60, $byName['Market-Poll']['repeat']);
        $this->assertSame(90 * 60, $byName['Market-AutoTrack']['duration']);
        $this->assertSame(90 * 60, $byName['Market-AutoTrack']['repeat']);
    }
}
