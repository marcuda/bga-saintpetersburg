<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Saint Petersburg The New Society implementation: © Nicolas Delaporte <nicolas.delaporte+bga@neutralite.org>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See https://en.boardgamearena.com/#!doc/Studio for more information.
 */
declare(strict_types = 1);
namespace Bga\Games\SaintPetersburgExpansion\States;

use Bga\GameFramework\SystemException;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SaintPetersburgExpansion\PickFromDiscardState;
use Bga\Games\SaintPetersburgExpansion\Game;
use Bga\Games\SaintPetersburgExpansion\StateId;

/**
 * This active player state ask a player to choose a card from discard and then buy add or discard it.
 */
class UsePrison extends PickFromDiscardState
{
    function __construct(protected Game $game)
    {
        parent::__construct($game, id: StateId::USE_DEBTORS_PRISON,
            description: clienttranslate('Debtor’s Prison: ${actplayer} must choose a card then take or discard'),
            descriptionMyTurn: clienttranslate('Debtor’s Prison: ${you} must choose a card from discard pile'));
    }

    /**
     * Player discards the card drawn with debtor’s prison
     * @param int $cardId A discarded card id to discard.
     * @param int $activePlayerId The active player id.
     * @return mixed The next state (NextPlayer or PlayerTurn).
     * @throws SystemException When the card can not be discarded.
     */
    #[PossibleAction]
    function actDiscardCard(int $cardId, int $activePlayerId)
    {
        $game = $this->game;
        // Verify discarded card
        $card = $game->cards->getCard($cardId);
        if ($card == null || $card['location'] != 'discard') {
            throw new SystemException("Impossible prison discard");
        }
        // Move card on top of discard pile.
        $game->cards->insertCardOnExtremePosition($cardId, 'discard', true);
        
        // Reuse end round discard notif arg
        $cards = [[
            'row' => ROW_DISCARD,
            'col' => $card['type_arg']
        ]];
        
        $msg = clienttranslate('${player_name} discards ${card_name}');
        $this->bga->notify->all('discard', $msg, array(
            'i18n' => array('card_name'),
            'player_name' => $game->getPlayerNameById($activePlayerId),
            'card_name' => $game->getCardName($card),
            'cards' => $cards
        ));
        
        // Reset pass counter.
        $game->setGameStateValue("num_pass", 0);
        $this->bga->playerStats->inc('actions_taken', 1, $activePlayerId);
        return $game->getNextState();
    }

    /**
     * This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
     * You can do whatever you want in order to make sure the turn of this player ends appropriately
     * (ex: play a random card).
     *
     * See more about Zombie Mode: https://en.doc.boardgamearena.com/Zombie_Mode
     *
     * Important: your zombie code will be called when the player leaves the game. This action is triggered
     * from the main site and propagated to the gameserver from a server, not from a browser.
     * As a consequence, there is no current player associated to this action. In your zombieTurn function,
     * you must _never_ use `getCurrentPlayerId()` or `getCurrentPlayerName()`,
     * but use the $playerId passed in parameter and $this->game->getPlayerNameById($playerId) instead.
     *
     * @param int $playerId The id of the player being a zombie.
     * @return string The next state.
     * @throws SystemException Should not happen.
     */
    function zombie(int $playerId, array $args)
    {
        // Level 0 zombie: discard last card.
        return $this->actDiscardCard(array_key_last($args[
        '_private'][$playerId]['possibleMoves']), $playerId);
    }
}

