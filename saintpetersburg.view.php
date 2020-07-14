<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * saintpetersburg.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in saintpetersburg_saintpetersburg.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
require_once( APP_BASE_PATH."view/common/game.view.php" );
  
class view_saintpetersburg_saintpetersburg extends game_view
{
    function getGameName() {
        return "saintpetersburg";
    }    

    function build_page( $viewArgs )
    {               
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        // Template block for card slots on board
        $this->page->begin_block("saintpetersburg_saintpetersburg", "square");

        $hor_scale = 90;
        $ver_scale = 120;
        for ($y=0; $y<2; $y++)
        {
            for ($x=0; $x<8; $x++)
            {
                $this->page->insert_block("square", array(
                    'X' => $x,
                    'Y' => $y,
                    'LEFT' => round((7 - $x) * $hor_scale + 20), // count right to left
                    'TOP' => round($y * $ver_scale + 232)
                ));
            }
        }

        // Template block for player boards
        // Get correct order relative to current player
        $players_ordered = $this->game->getPlayersInOrder();
        $this->page->begin_block( "saintpetersburg_saintpetersburg", "player" );
        foreach ( $players_ordered as $player_id )
        {
            $this->page->insert_block( "player", array(
                "PLAYER_ID" => $player_id,
                "PLAYER_COLOR" => $players[$player_id]['player_color'],
                "PLAYER_NAME" => $players[$player_id]['player_name']
            ) );
        }

        // Translate title for hand block
        $this->tpl['MY_HAND'] = self::_("My hand");

        // Active automatic pass message
        $this->tpl['AUTOPASS_MSG'] = self::_("You will automatically pass your turn until the next phase begins!");

        // Temporary publisher notice
        $publisher = html_entity_decode('Hans im Gl&uuml;ck'); // NOI18N
        $msg = self::_("A word from");
        $msg .= ' ' . $publisher . ': ';
        $msg .= self::_("the artwork for Saint Petersburg is being reworked and this temporary version will be replaced when the new artwork is ready.");
        $this->tpl['PUBLISHER_MSG'] = $msg;
        $this->tpl['PUBLISHER_ACK'] = self::_("Okay, got it!");

        /*********** Do not change anything below this line  ************/
    }
}
  

