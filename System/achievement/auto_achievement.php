<?php

function autoGenerateAchievement($con, $userid = null) {
    $created    = [];
    $userFilter = $userid ? "AND p.userid = " . intval($userid) : "";

    $sql = "
        SELECT
            p.id        AS participant_id,
            p.userid,
            e.EventId,
            e.EventName,
            e.EventType,
            e.EventDate
        FROM event_participant p
        JOIN event e ON e.EventId = p.eventid
        WHERE e.EventStatus = 'Ended'
          $userFilter
          AND NOT EXISTS (
              SELECT 1 FROM achievement a
              WHERE a.userid = p.userid
                AND a.event_id = e.EventId
                AND a.type = 'Certificate'
          )
    ";

    $result = $con->query($sql);
    if (!$result) {
        error_log("autoGenerateAchievement error: " . $con->error);
        return [];
    }

    while ($row = $result->fetch_assoc()) {
        $uid        = intval($row['userid']);
        $event_id   = intval($row['EventId']);
        $event_name = $con->real_escape_string($row['EventName']);
        $event_type = $con->real_escape_string($row['EventType']);
        $issued_date = $row['EventDate'];
        $title      = $con->real_escape_string("Certificate of Participation – " . $row['EventName']);

        $insert = $con->query("
            INSERT INTO achievement (userid, event_id, title, type, event_type, award_status, issued_date)
            VALUES ($uid, $event_id, '$title', 'Certificate', '$event_type', 'None', '$issued_date')
        ");

        if ($insert) {
            $created[] = [
                'title'      => "Certificate of Participation – " . $row['EventName'],
                'type'       => 'Certificate',
                'event_type' => $row['EventType'],
                'event_name' => $row['EventName'],
                'issued_date'=> $issued_date,
            ];
        }
    }

    return $created;
}