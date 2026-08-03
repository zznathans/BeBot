<?php
use PHPUnit\Framework\TestCase;

/*
Regression coverage for the security-group-based autoinvite path, which exists as two
near-identical copies in this file: Relay::connect() (runs once on connect) and
Relay::cron(300) (runs every 5 minutes). connect()'s copy used to query "FROM online"
(missing the #___ table prefix every other query in this file uses, including the UPDATE
two lines above it) - since that table doesn't exist as a bare name, the query came back
empty rather than erroring, and the unguarded $invitelist[0] check on that empty result
threw "Warning: Undefined array key 0" instead of just finding no one to invite (fixed in
one PR). The cron(300) copy already had the correct #___online prefix but had the exact
same unguarded $invitelist[0] check, so the warning kept happening on every 5-minute tick
even after connect()'s copy was fixed (fixed in a follow-up once it showed up live) -
these tests cover both copies so a regression to either shows up here.
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


    /** cron(300)'s own copy of the autoinvite path invites a matching candidate too. */
    public function testCronThreeHundredInvitesMatchingCandidate()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        $bot->db->seedSelect("security_members", array(array("Tickr", 0, "TestBot", 1, "Tickr")));

        $relay = new Relay($bot);
        // Skip the first-tick guildname-setup block (unrelated to autoinvite, and pulls in
        // more of Relay's own methods than this test needs to stub) so cron(300) exercises
        // only the autoinvite path being tested here.
        $relay->guildnameset = true;
        $relay->cron(300);

        $this->assertSame(array("Tickr"), $bot->chat->invitedNames);
    }


    /** cron(300) finding no invite candidates doesn't warn - same !empty() fix as connect(). */
    public function testCronThreeHundredDoesNothingWhenNoInviteCandidatesOnline()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        // No seedSelect for the online/security_members join - falls through to array().

        $relay = new Relay($bot);
        $relay->guildnameset = true;
        $relay->cron(300);

        $this->assertSame(array(), $bot->chat->invitedNames);
    }


    /** cron() ticks other than 300 (e.g. the "2sec" one this module also registers) don't touch autoinvite at all. */
    public function testCronIgnoresOtherIntervals()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->db->seedSelect("security_groups", array(array(1, "RelayBots")));
        $bot->db->seedSelect("security_members", array(array("Tickr", 0, "TestBot", 1, "Tickr")));

        $relay = new Relay($bot);
        $relay->cron(2);

        $this->assertSame(array(), $bot->chat->invitedNames);
    }


    /** connect() buddies every AutoinviteRelayGroup member not already on the buddylist. */
    public function testConnectBuddiesGroupMembersNotAlreadyBuddied()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array("Tickr1", "Tickr2"));
        $bot->player->seedId("Tickr1", 1001);
        $bot->player->seedId("Tickr2", 1002);
        // No seedSelect for security_groups/security_members - the existing invite query
        // isn't under test here, only the buddy-sync step that now runs before it.

        $relay = new Relay($bot);
        $relay->connect();

        $this->assertTrue($bot->chat->buddy_exists(1001));
        $this->assertTrue($bot->chat->buddy_exists(1002));
    }


    /** connect()'s buddy sync leaves an already-buddied group member's buddy status untouched. */
    public function testConnectDoesNotReBuddyAlreadyBuddiedGroupMembers()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array("Tickr1"));
        $bot->player->seedId("Tickr1", 1001);
        $bot->chat->buddy_add(1001);

        $relay = new Relay($bot);
        $relay->connect();

        $this->assertSame(array(1001 => true), $bot->chat->buddies);
    }


    /** cron(300)'s copy of the buddy sync buddies AutoinviteRelayGroup members too. */
    public function testCronThreeHundredBuddiesGroupMembers()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array("Tickr1"));
        $bot->player->seedId("Tickr1", 1001);

        $relay = new Relay($bot);
        $relay->guildnameset = true;
        $relay->cron(300);

        $this->assertTrue($bot->chat->buddy_exists(1001));
    }


    /** on_group_member_add() buddies a name added to the matching AutoinviteRelayGroup immediately, without waiting for cron(300). */
    public function testOnGroupMemberAddBuddiesMatchingGroupMember()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array());

        $relay = new Relay($bot);
        $relay->on_group_member_add(array('gid' => 1, 'name' => 'Tickr1', 'uid' => 1001));

        $this->assertTrue($bot->chat->buddy_exists(1001));
    }


    /** on_group_member_add() ignores additions to a different security group than AutoinviteRelayGroup. */
    public function testOnGroupMemberAddIgnoresOtherGroups()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", true);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array());

        $relay = new Relay($bot);
        $relay->on_group_member_add(array('gid' => 99, 'name' => 'SomeoneElse', 'uid' => 2002));

        $this->assertFalse($bot->chat->buddy_exists(2002));
    }


    /** on_group_member_add() does nothing when Relay.Autoinvite is off. */
    public function testOnGroupMemberAddDoesNothingWhenAutoinviteDisabled()
    {
        $bot = new FakeRelayBot();
        $bot->settings->set("Relay", "Autoinvite", false);
        $bot->settings->set("Relay", "AutoinviteRelayGroup", "RelayBots");
        $bot->security->seedGroup("relaybots", 1, array());

        $relay = new Relay($bot);
        $relay->on_group_member_add(array('gid' => 1, 'name' => 'Tickr1', 'uid' => 1001));

        $this->assertFalse($bot->chat->buddy_exists(1001));
    }
}
