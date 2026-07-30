<?php
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
require_once __DIR__ . '/Stubs/StubBot.php';
