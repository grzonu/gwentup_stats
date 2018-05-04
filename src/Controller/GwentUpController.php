<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class GwentUpController extends Controller
{

    /**
     * @Route("/gwentup/winrate/", name="winrate")
     */
    public function winrate()
    {
        $id = $this->getParameter('gwentup_id');
        $client = $this->get('eight_points_guzzle.client.gwentup');
        $response = $client->get('api/player/' . $id);
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        $seasons = [];
        $playerData = [];
        foreach ($data as $d) {
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
        foreach ($seasons as $s) {
            if ($s['DateEnd'] == null && in_array($s['OnlineMode'], array_keys($types))) {
                $currentSeasons[] = $s;
            }
        }

        $seasonsStats = [];
        foreach ($playerData['Seasons'] as $s) {
            $seasonsStats[$s['_id']] = $s;
        }

        $winrates = [];
        foreach ($currentSeasons as $s) {
            if (isset($seasonsStats[$s['_id']])) {
                $sData = $seasonsStats[$s['_id']];
                $games = $sData['Win'] + $sData['Lose'] + $sData['Draw'];
                $winrate = $games == 0 ? 0 : round(($sData['Win'] / $games) * 100, 2);
                $winrates[] = $types[$sData['OnlineMode']] . ': ' . $winrate . '% ' . '(W:' . $sData['Win'] . ' L:' . $sData['Lose'] . ' D:' . $sData['Draw'] . ')';
            }
        }

        return new Response(implode("     ", $winrates));
    }

    /**
     * @Route("/gwentup/mmr/", name="mmr")
     */
    public function mmr()
    {
        $id = $this->getParameter('gwentup_id');
        $client = $this->get('eight_points_guzzle.client.gwentup');
        $response = $client->get('api/player/' . $id);
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        $seasons = [];
        $playerData = [];
        foreach ($data as $d) {
            if ($d['type'] == "SEASONS_SUCCESS") {
                $seasons = $d['seasons'];
            }
            if ($d['type'] == "LOAD_PLAYER_SUCCESS") {
                $playerData = $d['data'];
            }
        }

        $types = [
            1 => 'Ladder',
            3 => 'Pro-Ladder'
        ];

        $currentSeasons = [];
        foreach ($seasons as $s) {
            if ($s['DateEnd'] == null && in_array($s['OnlineMode'], array_keys($types))) {
                $currentSeasons[] = $s;
            }
        }

        $seasonsStats = [];
        foreach ($playerData['Seasons'] as $s) {
            $seasonsStats[$s['_id']] = $s;
        }

        $winrates = [];
        foreach ($currentSeasons as $s) {
            $sData = $seasonsStats[$s['_id']];
            $winrates[] = $types[$sData['OnlineMode']] . ': MMR: ' . $sData['Mmr'] . ' Pozycja: ' . $sData['Position'];
        }

        return new Response(implode("     ", $winrates));
    }

    protected function shortUrl($url, $login, $token)
    {
        $client = $this->get('eight_points_guzzle.client.bitly');
        $response = $client->get('/v3/shorten?login=' . $login . '&apiKey=' . $token . '&longUrl=' . urlencode($url));
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        if ($data['status_code'] == 200) {
            return $data['data']['url'];
        }

        return $url;
    }

    /**
     * @Route("/gwentup/decklist/", name="decklist")
     */
    public function decklist(Request $request)
    {
        $id = $this->getParameter('gwentup_id');
        $login = $this->getParameter('bitly_login');
        $token = $this->getParameter('bitly_token');
        $client = $this->get('eight_points_guzzle.client.gwentup');
        $response = $client->get('api/player/' . $id);
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        $playerData = [];
        foreach ($data as $d) {
            if ($d['type'] == "LOAD_PLAYER_SUCCESS") {
                $playerData = $d['data'];
            }
        }
        $decks = $playerData['Decks'];
        $activeDecks = [];
        $now = new \DateTime();
        $cache = new FilesystemAdapter('app.cache');
        foreach ($decks as $deck) {
            if ($deck['IsActive'] == true) {
                $dt = new \DateTime($deck['DateLastUsed']);
                if ($now->getTimestamp() - $dt->getTimestamp() < 7 * 24 * 3600) {
                    $activeDecks[$deck['GwentDeckName']] = $deck;
                }
            }
        }
        $response = $client->get('api/player/' . $id . '/decks-statistics');
        $content = $response->getBody()->getContents();
        $data = \GuzzleHttp\json_decode($content, true);
        $deckStats = [];
        $cards = [];
        $factions = [
            2 => 'Potwory',
            32 => 'Skellige',
            4 => 'Nilfgaard',
            16 => 'Scoia\'tael',
            8 => 'Królestwa Północy'
        ];
        foreach ($data as $d) {
            if ($d['type'] == "LOAD_PLAYER_DECK_STATISTICS_SUCCESS") {
                $deckStats = $d['data'];
            }
            if ($d['type'] == "CARDS_SUCCESS") {
                $cards = $d['cards'];
            }
        }

        $showDecks = [];
        foreach ($activeDecks as $name => $deck) {
            foreach ($deckStats as $stats) {
                if ($stats['_id'] == $deck['GwentDeckId']) {
                    $win = $stats['win'];
                    $lose = $stats['lose'];
                    $draw = $stats['draw'];
                    $games = $win + $lose + $draw;
                    $winrate = round(($win / $games) * 100, 2);
                    if ($games >= 10 && $winrate >= 40) {
                        $deck['winrate'] = $winrate;
                        $deck['games'] = $games;
                        $leader = $cards[$stats['leaderId']]["Name"];
                        $faction = $factions[$cards[$stats['leaderId']]['FactionId']];
                        $deck['leader'] = $leader;
                        $deck['frakcja'] = $faction;
                        $showDecks[$name] = $deck;
                    }
                }
            }
        }
        usort($showDecks, function ($a, $b) {
            return $b['winrate'] - $a['winrate'];
        });
        $ret = [];
        foreach ($showDecks as $deck) {
            $link = "https://gwentup.com/player/" . $id . '/decks/' . $deck['GwentDeckId'];
            $hash = md5($link);
            $item = $cache->getItem($hash);
            $shortUrl = '';
            if ($item->isHit()) {
                $shortUrl = $item->get();
            } else {
                $shortUrl = $this->shortUrl($link, $login, $token);
                if ($shortUrl != $link) {
                    $item->set($shortUrl);
                    $cache->save($item);
                }
            }
            $row = $deck['frakcja'] . '|' . $deck['leader'] . ' ' . $deck['GwentDeckName'] . '(WR: ' . $deck['winrate'] . '%) ' . $shortUrl;
            $ret[] = $row;
        }

        return new Response(implode("     ", $ret));
    }

}
