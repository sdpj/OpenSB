<?php

namespace Orange\Pages;

use Orange\MiscFunctions;
use Orange\BettyException;
use Orange\CommentLocation;
use Orange\Comments;
use Orange\Database;
use Orange\SubmissionData;

/**
 * Backend code for the profile page.
 *
 * @since 0.1.0
 */
class Profile
{
    private \Orange\Database $database;
    private $data;
    private $is_own_profile;
    public function __construct(\Orange\Orange $betty, $username)
    {
        global $auth;
        $this->database = $betty->getBettyDatabase();
        $this->data = $this->database->fetch("SELECT u.* FROM users u WHERE u.name = ?", [$username]);

        if ($this->database->fetch("SELECT * FROM bans WHERE userid = ?", [$this->data["id"]]))
        {
            $betty->Notification("This user is banned.", "/");
        }

        if ($this->data["id"] == $auth->getUserID())
        {
            $this->is_own_profile = true;
        }
    }

    public function getData(): array
    {
        return [
            "username" => $this->data["name"],
            "displayname" => $this->data["title"],
            "about" => ($this->data['about'] ?? false),
            "color" => $this->data["customcolor"],
            "joined" => $this->data["joined"],
            "connected" => $this->data["lastview"],
            "is_current" => $this->is_own_profile,
            "featured_submission" => $this->getSubmissionFromFeaturedID(),
        ];
    }

    private function getSubmissionFromFeaturedID()
    {
        global $auth;

        // featured_submission, replaces the unused "lastpost" column in the users table.

        // if user hasen't specified anything, don't bother.
        if ($this->data["featured_submission"] == 0) { return false; }

        $submission = new SubmissionData($this->database, $this->data["featured_submission"]);
        $data = $submission->getData();
        $bools = $submission->bitmaskToArray();

        // IF:
        // * The submission is taken down, and/or
        // * The submission no longer exists and/or
        // * The submission's author is not the user whose profile we're looking at and/or
        // * The submission is not available to guests and the user isn't signed in and/or
        // * TODO: The submission is privated...
        // then simply just return false, so we don't show the featured submission.
        if (
            $submission->getTakedown()
            || !$data
            || ($data["author"] != $this->data["id"])
            || ($bools["block_guests"] && !$auth->isUserLoggedIn())
        )
        {
            return false;
        } else {
            return [
                "title" => $data["title"],
                "id" => $data["video_id"],
                "type" => $data["post_type"],
            ];
        }
    }
}