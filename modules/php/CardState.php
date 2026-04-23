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
namespace Bga\Games\SaintPetersburg;

use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFramework\SystemException;
use Bga\GameFramework\UserException;
use Bga\Games\SaintPetersburg\Game;
use Bga\Games\SaintPetersburg\StateId;
use Bga\Games\SaintPetersburg\States\NextPlayer;

/**
 * Base state of states allowing to buy or add a card.
 */
class CardState extends GameState
{
    function __construct(protected Game $game, StateId $id, string $description, string $descriptionMyTurn)
    {
        parent::__construct($game, id: $id->value, type: StateType::ACTIVE_PLAYER,
            description: $description, descriptionMyTurn: $descriptionMyTurn);
    }
    
    /**
     * Player adds a card to their hand.
     * @param int $row The board row or the observatory card type.
     * @param int $col The board column (meaningless if row is the observatory card type).
     * @param int $activePlayerId The active player id.
     * @return string The next state (NextPlayer).
     */
    protected function addCard(int $row, int $col, int $activePlayerId): string
    {
        $card = $this->getSelectedCard($row, $col);
        
        // Verify player hand is not full
        if ($this->game->isHandFull($activePlayerId)) {
            throw new UserException(clienttranslate("Your hand is full"));
        }
        // Add to hand
        $dest = 'hand';
        $notif = 'addCard';
        $msg = clienttranslate('${player_name} adds ${card_name} to their hand');
        $this->cardAction((int) $card['id'], - 1, $row, 0, $dest, $notif, $msg, $activePlayerId);
        return NextPlayer::class;
    }
    
    /**
     * Player buys a card.
     * @param int $row The board row or the observatory card type.
     * @param int $col The board column (meaningless if row is the observatory card type).
     * @param int $activePlayerId The active player id.
     * @param int $trade_id The traded card id or -1 if no traded card.
     * @return string The next state (NextPlayer).
     */
    protected function buyCard(int $row, int $col, int $activePlayerId, int $trade_id): string
    {
        $game = $this->game;
        $card = $this->getSelectedCard($row, $col);
        $card_id = (int) $card['id'];
        
        // Verify trade if needed
        if ($game->isTrading($card)) {
            $this->checkTrade($card, $trade_id, $activePlayerId);
        } else if ($trade_id > 0) {
            throw new SystemException("Impossible buy with trade");
        }
        
        // Verify player can pay cost
        $card_cost = $game->getCardCost($card_id, $row, $trade_id);
        $rubles = $game->getRubles($activePlayerId);
        if ($card_cost > $rubles) {
            throw new UserException(clienttranslate("You do not have enough rubles"));
        }
        // Add card to player table
        $dest = 'table';
        $notif = 'buyCard';
        if ($trade_id > 0) {
            $msg = clienttranslate('${player_name} buys ${card_name}, displacing ${trade_name}, for ${card_cost} Ruble(s)');
        } else {
            $msg = clienttranslate('${player_name} buys ${card_name} for ${card_cost} Ruble(s)');
        }
        $this->cardAction($card_id, $trade_id, $row, $card_cost, $dest, $notif, $msg, $activePlayerId);
        return NextPlayer::class;
    }
    
    /**
     * Perform the appropriate action for the given card and destination.
     *
     * Reduces duplication of code in main card actions (buy/add/play).
     * @param int $card_id The card id.
     * @param int $trade_id The traded card id or -1 if no trade.
     * @param int $card_row The card row.
     * @param int $card_cost The card cost.
     * @param string $dest The card destination.
     * @param string $notif The notification name.
     * @param string $msg The notification message.
     * @param int $playerId The player id.
     */
    protected function cardAction(int $card_id, int $trade_id, int $card_row, int $card_cost, string $dest, string $notif,
        string $msg, int $playerId)
    {
        $game = $this->game;
        $card = $game->cards->getCard($card_id);
        $card_idx = $card['type_arg'];
        
        // Pay cost and take card
        $game->incRubles($playerId, - $card_cost);
        $game->cards->moveCard($card_id, $dest, $playerId);
        
        // Stats
        $this->bga->playerStats->inc('rubles_spent', $card_cost, $playerId);
        if ($dest == 'table') {
            $this->bga->playerStats->inc('cards_bought', 1, $playerId);
            if ($trade_id > 0) {
                $this->bga->playerStats->inc('cards_traded', 1, $playerId);
            }
        } else if ($dest == 'hand') {
            $this->bga->playerStats->inc('cards_added', 1, $playerId);
        }
        
        if ($trade_id > 0) {
            // Discard displaced card
            $game->cards->playCard($trade_id);
            
            // Get info for log
            $trade = $game->cards->getCard($trade_id);
            $trade_name = $game->getCardName($trade);
        } else {
            $trade_name = '';
        }
        
        // Income
        if ($dest == 'table') {
            $income = $game->getIncome($playerId);
        } else {
            // No change to report
            $income = null;
        }
        
        $this->bga->notify->all($notif, $msg, [
            'i18n' => ['card_name', 'trade_name'],
            'player_id' => $playerId,
            'player_name' => $game->getPlayerNameById($playerId),
            'card_name' => $game->getCardName($card),
            'card_id' => $card_id,
            'card_idx' => $card_idx,
            'card_loc' => $card['location_arg'],
            'card_row' => $card_row,
            'card_cost' => $card_cost,
            'trade_id' => $trade_id,
            'trade_name' => $trade_name,
            'aristocrats' => $game->uniqueAristocrats($playerId),
            'income' => $income
        ]);
        
        // Reset globals
        $game->setGameStateValue("activated_observatory", - 1);
        $game->setGameStateValue("num_pass", 0);
        $this->bga->playerStats->inc('actions_taken', 1, $playerId);
    }
    
    /**
     * Verify that given trading card can displace selected card
     * @param array $card Bought card from a Deck instance.
     * @param int $disp_id Displaced card id.
     * @param int $player_id Player id.
     */
    protected function checkTrade(array $card, int $disp_id, int $player_id)
    {
        $game = $this->game;
        // Verify displaced card exists and owned by player
        $disp_card = $game->cards->getCard($disp_id);
        if ($disp_card == null ||
            $disp_card['location'] != 'table' ||
            $disp_card['location_arg'] != $player_id)
        {
            throw new SystemException("Impossible trade card");
        }
        
        // Verify cards are of correct type to trade
        $card_info = $game->getCardInfo($card);
        $disp_info = $game->getCardInfo($disp_card);
        if ($card_info['card_trade_type'] != $disp_info['card_type'] ||
            ($disp_info['card_type'] == PHASE_WORKER &&
                $card_info['card_worker_type'] != $disp_info['card_worker_type'] &&
                $disp_info['card_worker_type'] != WORKER_ALL))
        {
            throw new UserException(clienttranslate("Wrong type of card to displace"));
        }
        
        // Check if trading used Observatory
        if ($disp_card['type_arg'] == CARD_OBSERVATORY) {
            $obs = $game->getObservatory($disp_id);
            if ($obs['used']) {
                throw new UserException(clienttranslate("You cannot displace an Observatory after using it"));
            }
        }
    }
    
    /**
     * Return the card at given board location.
     *
     * @param int $row The board row or the observatory card type.
     * @param int $col The board column (meaningless if row is the observatory card type).
     * @return array A card.
     * @throws SystemException If no card exist at given location.
     */
    private function getSelectedCard(int $row, int $col): array
    {
        $game = $this->game;
        // Get card from correct location
        if ($row == 0) {
            $loc = TOP_ROW;
        } else if ($row == 1) {
            $loc = BOTTOM_ROW;
        } else if ($row == ROW_OBSERVATORY) {
            $loc = 'obs_tmp';
            $col = $game->getActivePlayerId();
        }
        $cards = $game->cards->getCardsInLocation($loc, $col);
        
        // Verify a card exists here
        if (count($cards) != 1) {
            throw new SystemException("Impossible selection");
        }
        return array_shift($cards);
    }
}

