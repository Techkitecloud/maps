<?php

/**
 * Nextcloud - maps
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2019
 */

namespace OCA\Maps\Service;

use OCP\IL10N;
use OCP\ILogger;
use OCP\DB\QueryBuilder\IQueryBuilder;

class TracksService {

    private $l10n;
    private $logger;
    private $qb;

    public function __construct (ILogger $logger, IL10N $l10n) {
        $this->l10n = $l10n;
        $this->logger = $logger;
        $this->qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
    }

    /**
     * @param string $userId
     */
    public function getTracksFromDB($userId) {
        $tracks = [];
        $qb = $this->qb;
        $qb->select('id', 'file_id', 'color')
            ->from('maps_tracks', 't')
            ->where(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
            );
        $req = $qb->execute();

        while ($row = $req->fetch()) {
            array_push($tracks, [
                'id' => intval($row['id']),
                'file_id' => intval($row['file_id']),
                'color' => $row['color']
            ]);
        }
        $req->closeCursor();
        $qb = $qb->resetQueryParts();
        return $tracks;
    }

    public function getTrackFromDB($id, $userId=null) {
        $track = null;
        $qb = $this->qb;
        $qb->select('id', 'file_id', 'color')
            ->from('maps_tracks', 't')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );
        if ($userId !== null) {
            $qb->andWhere(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
            );
        }
        $req = $qb->execute();

        while ($row = $req->fetch()) {
            $track = [
                'id' => intval($row['id']),
                'file_id' => intval($row['file_id']),
                'color' => $row['color']
            ];
            break;
        }
        $req->closeCursor();
        $qb = $qb->resetQueryParts();
        return $track;
    }

    public function addTrackToDB($userId, $fileId) {
        $qb = $this->qb;
        $qb->insert('maps_tracks')
            ->values([
                'user_id' => $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
                'file_id' => $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)
            ]);
        $req = $qb->execute();
        $trackId = $qb->getLastInsertId();
        $qb = $qb->resetQueryParts();
        return $trackId;
    }

    public function editTrackInDB($id, $color) {
        $qb = $this->qb;
        $qb->update('maps_tracks')
            ->set('color', $qb->createNamedParameter($color, IQueryBuilder::PARAM_STR))
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );
        $req = $qb->execute();
        $qb = $qb->resetQueryParts();
    }

    public function deleteTrackFromDB($id) {
        $qb = $this->qb;
        $qb->delete('maps_tracks')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );
        $req = $qb->execute();
        $qb = $qb->resetQueryParts();
    }

    public function deleteTracksFromDB($ids, $userId) {
        $qb = $this->qb;
        $qb->delete('maps_tracks')
            ->where(
                $qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
            );
        if (count($ids) > 0) {
            $or = $qb->expr()->orx();
            foreach ($ids as $id) {
                $or->add($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
            }
            $qb->andWhere($or);
        }
        else {
            return;
        }
        $req = $qb->execute();
        $qb = $qb->resetQueryParts();
    }

}