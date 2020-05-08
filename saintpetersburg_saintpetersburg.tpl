{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- SaintPetersburg implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="decks" style="display: flex;">
    <div id="deck_Worker" class="cardback deck" style="background-position: 0px 0px;">31</div>
    <div id="deck_Building" class="cardback deck" style="background-position: -30px 0px;">28</div>
    <div id="deck_Aristocrat" class="cardback deck" style="background-position: -60px 0px;">27</div>
    <div id="deck_Trading" class="cardback deck" style="background-position: -90px 0px;">30</div>
</div>

<div id="board">
	<!-- BEGIN square -->
	<div id="square_{X}_{Y}" class="square" style="left: {LEFT}px; top: {TOP}px;"></div>
	<!-- END square -->

	<div id="cards">
	</div>
</div>

<div id="myhand_wrap" class="whiteblock">
    <h3>{MY_HAND}</h3>
    <div id="myhand"></div>
</div>

<div id="playertables">
    <!-- BEGIN player -->
    <div id="playertable_{PLAYER_ID}_wrap" class="whiteblock">
	<h3 style="color:#{PLAYER_COLOR}">{PLAYER_NAME}</h3>
	<div id="playertable_{PLAYER_ID}"></div>
    </div>
    <!-- END player -->
</div>

<div id="button_1"></div>
<div id="button_2"></div>
<div id="button_3"></div>
<div id="button_4"></div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_card = '<div class="card" id="card_${col}_${row}" style="background-position:-${x}px -${y}px"></div>';
var jstpl_card_content =
'<div id="card_content_${id}">' +
    '<div id="card_content_${id}_mask" class="maskcard"></div>' +
    '<div id="card_content_${id}_active" class="activecard"><a href="#">Activate</a></div>' +
'</div>';
var jstpl_player_board = 
'<div class="stp_board">' +
	'<div id="rubleicon_p${id}" class="rubleicon nolinebreak"><span id="rublecount_p${id}">?</span></div>&nbsp' + 
	'<div id="cardicon_p${id}" class="cardback nolinebreak" style="background-position: -90px 0px;"><span id="handcount_p${id}">0</span></div>&nbsp' + 
	'<div id="token_p${id}" class="token token_worker"></div>&nbsp' + 
	'<div id="token2_p${id}" class="token token_building"></div>&nbsp' + 
'</div>';

</script>  

{OVERALL_GAME_FOOTER}
