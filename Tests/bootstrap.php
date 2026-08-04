<?php
// Normally defined by Sources/Bot.php, which isn't loaded in this unit-test
// environment (it pulls in the real AO/AoC connection stack). Defined here
// so Commodities/00_BasePassiveModule.php's output_destination() - which
// tests reference directly via these bitflags - has them available.
if (!defined('SAME')) {
    define('SAME', 1);
}
if (!defined('TELL')) {
    define('TELL', 2);
}
if (!defined('GC')) {
    define('GC', 4);
}
if (!defined('PG')) {
    define('PG', 8);
}
if (!defined('RELAY')) {
    define('RELAY', 16);
}
if (!defined('IRC')) {
    define('IRC', 32);
}

require_once __DIR__ . '/../Commodities/BotError.php';

require_once __DIR__ . '/../Sources/Log/LogRecord.php';
require_once __DIR__ . '/../Sources/Log/LogFormatterInterface.php';
require_once __DIR__ . '/../Sources/Log/PlainTextLogFormatter.php';
require_once __DIR__ . '/../Sources/Log/JsonLogFormatter.php';
require_once __DIR__ . '/../Sources/Log/LogHandlerInterface.php';
require_once __DIR__ . '/../Sources/Log/ConsoleLogHandler.php';
require_once __DIR__ . '/../Sources/Log/FileLogHandler.php';
require_once __DIR__ . '/../Sources/Log/DbLogHandler.php';
require_once __DIR__ . '/../Sources/Log/LogDispatcher.php';

require_once __DIR__ . '/Stubs/FakeDb.php';
require_once __DIR__ . '/Stubs/FakeSettings.php';
require_once __DIR__ . '/Stubs/RecursingFakeSettings.php';
require_once __DIR__ . '/Stubs/StubBot.php';

require_once __DIR__ . '/../Commodities/00_BasePassiveModule.php';
require_once __DIR__ . '/../Commodities/01_BaseActiveModule.php';
require_once __DIR__ . '/Stubs/FakeSettingsCore.php';
require_once __DIR__ . '/Stubs/FakeTimerCore.php';
require_once __DIR__ . '/Stubs/FakeNoOpCore.php';
require_once __DIR__ . '/Stubs/FakeMarketBot.php';
require_once __DIR__ . '/Stubs/FakeOutputDestinationBot.php';
// Modules/Ao/Market.php self-instantiates against whatever $bot is in scope the moment it's
// required (the framework's normal module auto-load convention) - give it a throwaway fake here
// so that one-time side effect doesn't fatal error. Tests construct their own fresh Market($bot)
// instances against their own FakeMarketBot to exercise the constructor for real.
$bot = new FakeMarketBot();
require_once __DIR__ . '/../Modules/Ao/Market.php';

require_once __DIR__ . '/Stubs/FakeRelayDb.php';
require_once __DIR__ . '/Stubs/FakeChatCore.php';
require_once __DIR__ . '/Stubs/FakeDispatcher.php';
require_once __DIR__ . '/Stubs/FakeSecurityCore.php';
require_once __DIR__ . '/Stubs/FakePlayerCore.php';
require_once __DIR__ . '/Stubs/FakeRelayBot.php';
// Same self-instantiation guard as Market.php above.
$bot = new FakeRelayBot();
require_once __DIR__ . '/../Modules/Relay.php';
