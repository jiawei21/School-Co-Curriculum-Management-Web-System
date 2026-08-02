<?php

function autoEndEvents($con) {
    $con->query("
        UPDATE event
        SET EventStatus = 'Ended'
        WHERE EventStatus = 'Approved'
          AND (
              EventDate < CURDATE()
              OR (EventDate = CURDATE() AND EventEndTime <= CURTIME())
          )
    ");
}


function calcHours($startTime, $endTime) {
    $start   = strtotime($startTime);
    $end     = strtotime($endTime);
    $seconds = $end - $start;
    if ($seconds <= 0) return 0;
    return round($seconds / 3600, 2);
}

/**
 * @param  mysqli 
 * @param  int|null 
 * @return array  
 */
function autoGenerateMerit($con, $userid = null) {
    $created    = [];
    $userFilter = $userid ? "AND p.userid = " . intval($userid) : "";

    $sql = "
        SELECT
            p.id            AS participant_id,
            p.userid,
            e.EventId,
            e.EventName     AS activity_name,
            e.EventType     AS category,
            e.EventDate     AS merit_date,
            e.EventStartTime,
            e.EventEndTime
        FROM event_participant p
        JOIN event e ON e.EventId = p.eventid
        WHERE p.merit_created = 0
          $userFilter
          AND e.EventStatus = 'Ended'
    ";

    $result = $con->query($sql);
    if (!$result) {
        error_log("autoGenerateMerit error: " . $con->error);
        return [];
    }

    while ($row = $result->fetch_assoc()) {
        $participant_id = intval($row['participant_id']);
        $uid            = intval($row['userid']);
        $event_id       = intval($row['EventId']);
        $activity_name  = $con->real_escape_string($row['activity_name']);
        $category       = $con->real_escape_string($row['category']);
        $merit_date     = $row['merit_date'];

        $hours = calcHours($row['EventStartTime'], $row['EventEndTime']);
        if ($hours <= 0) {
            $con->query("UPDATE event_participant SET merit_created = 1 WHERE id = $participant_id");
            continue;
        }

        $check = $con->query("
            SELECT merit_id FROM merit
            WHERE userid = $uid AND event_id = $event_id
            LIMIT 1
        ");
        if ($check && $check->num_rows > 0) {
            $con->query("UPDATE event_participant SET merit_created = 1 WHERE id = $participant_id");
            continue;
        }

        $insert = $con->query("
            INSERT INTO merit (userid, event_id, activity_name, hours, merit_date)
            VALUES ($uid, $event_id, '$activity_name', $hours, '$merit_date')
        ");

        if ($insert) {
            $con->query("UPDATE event_participant SET merit_created = 1 WHERE id = $participant_id");
            $created[] = [
                'activity_name' => $row['activity_name'],
                'category'      => $row['category'],
                'hours'         => $hours,
                'merit_date'    => $merit_date,
            ];
        } else {
            error_log("autoGenerateMerit insert error: " . $con->error);
        }
    }

    return $created;
}