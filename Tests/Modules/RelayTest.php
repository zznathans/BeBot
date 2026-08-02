<?php
use PHPUnit\Framework\TestCase;

/*
Regression coverage for Relay::connect()'s security-group-based autoinvite path. It used
to query "FROM online" (missing the #___ table prefix every other query in this file
uses, including the UPDATE two lines above it) - since that table doesn't exist as a bare
name, the query came back empty rather than erroring, and the unguarded $invitelist[0]
check on that empty result threw "Warning: Undefined array key 0" instead of just finding
no one to invite. Same unguarded-array-access issue existed one block up for
$security_groups_gid[0][0]. Fixed to query #___online and to check with !empty() instead
of indexing a possibly-empty result directly - these tests pin both the correct SQL and
the safe-empty-result behavior so a regression back to either shows up here.
*/
class RelayTest extends TestCase
{
    /** connect() queries the *prefixed* online table, not a bare "online". */
    public function testConnectQueriesPrefixedOnlineTable()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        $bot->db->seedSelect("security_members", array(array("Tickr", 0, "TestBot", 1, "Tickr")));

        $relay = new Relay($bot);
        $relay->connect();

        $onlineQueries = array_values(array_filter($bot->db->queries, function ($sql) {
            return stripos($sql, "security_members") !== false && stripos($sql, "status_pg") !== false;
        }));

        $this->assertCount(1, $onlineQueries, "expected exactly one invite-candidates query");
        $this->assertStringContainsString("#___online", $onlineQueries[0]);
        $this->assertStringNotContainsString("FROM online ", $onlineQueries[0]);
    }


    /** A matching invite candidate actually gets invited. */
    public function testConnectInvitesMatchingCandidate()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        $bot->db->seedSelect("security_members", array(array("Tickr", 0, "TestBot", 1, "Tickr")));

        $relay = new Relay($bot);
        $relay->connect();

        $this->assertSame(array("Tickr"), $bot->chat->invitedNames);
    }


    /** No matching security group - no invite attempted, no warning from indexing an empty result. */
    public function testConnectDoesNothingWhenSecurityGroupNotFound()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "NoSuchGroup");
        // No seedSelect for security_groups - select() falls through to its array() default.

        $relay = new Relay($bot);
        $relay->connect();

        $this->assertSame(array(), $bot->chat->invitedNames);
    }


    /** Security group exists but no one currently online matches it - no invite, no warning. */
    public function testConnectDoesNothingWhenNoInviteCandidatesOnline()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        // No seedSelect for the online/security_members join - falls through to array().

        $relay = new Relay($bot);
        $relay->connect();

        $this->assertSame(array(), $bot->chat->invitedNames);
    }


    /** Autoinvite off entirely - the security-group path is never queried. */
    public function testConnectSkipsAutoinviteWhenDisabled()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", false);

        $relay = new Relay($bot);
        $relay->connect();

        $groupQueries = array_filter($bot->db->queries, function ($sql) {
            return stripos($sql, "security_groups") !== false;
        });
        $this->assertCount(0, $groupQueries);
        $this->assertSame(array(), $bot->chat->invitedNames);
    }
}
