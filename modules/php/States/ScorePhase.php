<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburg\States;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;

class ScorePhase extends GameState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::SCORE_PHASE->value, type: StateType::GAME);
    }
    
    /**
     * Score end of phase and move to next phase or end game
     * @return mixed The next state (NextPhase, UsePub, EndGame).
     */
    function onEnteringState()
    {
        $game = $this->game;
        // Get phase status
        $current_phase = $game->getGameStateValue('current_phase') % 4;
        $new_round = ($current_phase == 3);
        
        // End game if last phase of final round just finished
        if ($new_round && $game->getGameStateValue("last_round")) {
            $this->finalScoring();
            return StateId::END_GAME->value;
        }
        
        // Score phase just completed
        $phase = $game->phases[$current_phase];
        $this->scorePhase($phase);
        
        if ($phase == PHASE_BUILDING) {
            // Allow pub to be used if owned
            return UsePub::class;
        }
        return NextPhase::class;
    }
    
    /*
     * Compute the scores at the end of the given phase
     */
    function scorePhase(string $phase)
    {
        if ($phase == PHASE_TRADING) {
            // No scoring after trading card phase.
            return;
        }
        
        $game = $this->game;
        $players = $game->loadPlayersBasicInfos();
        $scores = array();
        foreach ($players as $player_id => $player) {
            list($points, $rubles) = $game->computeScoring($player_id, $phase);
            
            // Update scores, stats, and log
            $scores[$player_id] = $this->bga->playerScore->inc($player_id, $points, null);
            $this->bga->playerStats->inc('points_total', $points, $player_id);
            $this->bga->playerStats->inc('points_' . $phase, $points, $player_id);
            
            $game->incRubles($player_id, $rubles);
            $this->bga->playerStats->inc('rubles_total', $rubles, $player_id);
            $this->bga->playerStats->inc('rubles_' . $phase, $rubles, $player_id);
            
            $msg = clienttranslate('${player_name} earns ${rubles} Ruble(s) and ${points} Point(s)');
            $this->bga->notify->all('scorePhase', $msg, array(
                'player_id' => $player_id,
                'player_name' => $player['player_name'],
                'points' => $points,
                'rubles' => $rubles
            ));
        }
        
        // Notify to update scores on client
        $this->bga->notify->all('newScores', "", array(
            'scores' => $scores
        ));
    }
    
    /*
     * Compute end game scoring and set final results
     */
    function finalScoring()
    {
        $game = $this->game;
        $players = $game->loadPlayersBasicInfos();
        $scores = array();
        $rubles = array();
        foreach ($players as $player_id => $player) {
            // Each different aristocrat (up to 10) is worth that many points
            // 1 = 1, 2 = 1+2 = 3, 3 = 1+2+3 = 6, ... 10 = 1+2+3+...+10 = 55
            // or more simply: n(n+1)/2
            $num_ari = $game->uniqueAristocrats($player_id);
            $points_ari = min(55, $num_ari * ($num_ari + 1) / 2);
            $this->bga->playerScore->inc($player_id, $points_ari, null);
            $this->playerStats->set('points_aristocrats_end', $points_ari, $player_id);
            
            $msg = clienttranslate('Final scoring: ${player_name} earns ${points_ari} Point(s) for ${num_ari} Aristocrat type(s)');
            $this->bga->notify->all('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_ari' => $points_ari,
                'num_ari' => $num_ari,
            ));
            
            // 1 per 10 rubles, ignoring any remainder
            // Players trade these rubles for points
            $num_rubles = $game->getRubles($player_id);
            $points_rubles = intdiv($num_rubles, 10);
            $num_rubles = 10 * $points_rubles;
            $this->bga->playerScore->inc($player_id, $points_rubles, null);
            $rubles[$player_id] = $game->incRubles($player_id, -1 * $num_rubles);
            $this->bga->playerStats->set( 'points_rubles_end', $points_rubles, $player_id);
            
            $msg = clienttranslate('Final scoring: ${player_name} earns ${points_rubles} Point(s) for ${num_rubles} Ruble(s)');
            $this->bga->notify->all('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_rubles' => $points_rubles,
                'num_rubles' => $num_rubles,
            ));
            
            // -5 per card left in hand
            $num_hand = count($game->cards->getPlayerHand($player_id));
            $points_hand = -5 * $num_hand;
            // set final score to report
            $scores[$player_id] = $this->bga->playerScore->inc($player_id, $points_hand, null);
            $this->bga->playerStats->set( 'points_hand_end', $points_hand, $player_id);
            
            $msg = clienttranslate('Final scoring: ${player_name} loses ${points_hand} Points for ${num_hand} card(s) in hand');
            $this->bga->notify->all('message', $msg, array(
                'player_name' => $player['player_name'],
                'points_hand' => $points_hand,
                'num_hand' => $num_hand,
            ));
            
        }
        
        $this->bga->notify->all('newScores', "", array(
            'scores' => $scores,
            'rubles' => $rubles,
        ));
    }
}

