<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * saintpetersburg.action.php
 *
 * SaintPetersburg main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/saintpetersburg/saintpetersburg/myAction.html", ...)
 *
 */
  
  
class action_saintpetersburg extends APP_GameAction
{ 
    // Constructor: please do not modify
    public function __default()
    {
        if( self::isArg( 'notifwindow') )
        {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
        }
        else
        {
            $this->view = "saintpetersburg_saintpetersburg";
            self::trace( "Complete reinitialization of board game" );
        }
    } 
        
    // Player selects a card on the board
    public function selectCard()
    {
        self::setAjaxMode();
        $row = self::getArg("row", AT_posint, true); // card location on board
        $col = self::getArg("col", AT_posint, true);
        $result = $this->game->selectCard($row, $col);
        self::ajaxResponse();
    }

    // Player clicks the 'Buy' button
    public function buyCard()
    {
        self::setAjaxMode();
        $result = $this->game->buyCard();
        self::ajaxResponse();
    }

    // Player clicks the 'Add to hand' button
    public function addCard()
    {
        self::setAjaxMode();
        $result = $this->game->addCard();
        self::ajaxResponse();
    }

    // Player clicks the 'Cancel' button
    public function cancelSelect()
    {
        self::setAjaxMode();
        $result = $this->game->cancelSelect();
        self::ajaxResponse();
    }

    // Player clicks the 'Pass' button
    public function pass()
    {
        self::setAjaxMode();
        $result = $this->game->pass();
        self::ajaxResponse();
    }

    // Player clicks a card in their hand
    public function playCard()
    {
        self::setAjaxMode();
        $card_id = self::getArg("card_id", AT_posint, true); // hand card id
        $result = $this->game->playCard($card_id);
        self::ajaxResponse();
    }

    // Player selects a card on their board to displace
    public function tradeCard()
    {
        self::setAjaxMode();
        $card_id = self::getArg("card_id", AT_posint, true); // displaced card id
        $result = $this->game->tradeCard($card_id);
        self::ajaxResponse();
    }

    // Player clicks the 'Buy' button for the Pub bonus
    public function buyPoints()
    {
        self::setAjaxMode();
        $points = self::getArg("points", AT_posint, true); // number points
        $result = $this->game->buyPoints($points);
        self::ajaxResponse();
    }

    // Player clicks an active Observatory on their board
    public function useObservatory()
    {
        self::setAjaxMode();
        $card_id = self::getArg("card_id", AT_posint, true); // observatory id
        $result = $this->game->useObservatory($card_id);
        self::ajaxResponse();
    }

    // Player clicks a deck while using Observatory
    public function drawObservatoryCard()
    {
        self::setAjaxMode();
        $deck = self::getArg("deck", AT_alphanum, true); // deck name
        $result = $this->game->drawObservatoryCard($deck);
        self::ajaxResponse();
    }

    // Player clicks the 'Buy' button while using Observatory
    public function obsBuy()
    {
        self::setAjaxMode();
        $result = $this->game->obsBuy();
        self::ajaxResponse();
    }

    // Player clicks the 'Add to hand' button while using Observatory
    public function obsAdd()
    {
        self::setAjaxMode();
        $result = $this->game->obsAdd();
        self::ajaxResponse();
    }

    // Player clicks the 'Discard' button while using Observatory
    public function obsDiscard()
    {
        self::setAjaxMode();
        $result = $this->game->obsDiscard();
        self::ajaxResponse();
    }

}
