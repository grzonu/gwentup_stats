<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GwentUpController extends Controller
{
    /**
     * @Route("/gwentup/winrate/{id}", name="winrate")
     */
    public function winrate($id)
    {
        $client = $this->get('eight_points_guzzle.client.gwentup');
        $response = $client->get('api/player/' . $id);
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        $seasons = [];
        $playerData = [];
        foreach($data as $d) {
            if ($d['type'] == "SEASONS_SUCCESS") {
                $seasons = $d['seasons'];
            }
            if ($d['type'] == "LOAD_PLAYER_SUCCESS") {
                $playerData = $d['data'];
            }
        }

        $types = [
            1 => 'Ladder',
            3 => 'Pro-Ladder',
            4 => 'Arena'
        ];

        $currentSeasons = [];
        foreach($seasons as $s) {
            if($s['DateEnd'] == null && in_array($s['OnlineMode'], array_keys($types))) {
                $currentSeasons[] = $s;
            }
        }

        $seasonsStats = [];
        foreach($playerData['Seasons'] as $s) {
            $seasonsStats[$s['_id']] = $s;
        }

        $winrates = [];
        foreach($currentSeasons as $s) {
            $sData = $seasonsStats[$s['_id']];
            $games = $sData['Win'] + $sData['Lose'] + $sData['Draw'];
            $winrate = round($sData['Win'] / $games, 2);

            $winrates[] = $types[$sData['OnlineMode']] . ': ' . $winrate .'% '  . '(W:'.$sData['Win'] .' L:'. $sData['Lose']. ' D:' .$sData['Draw'].')';
        }
        return new Response(implode("\r\n", $winrates));
    }
}
