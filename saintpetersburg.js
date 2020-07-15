/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * SaintPetersburg implementation : © Dan Marcus <bga.marcuda@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * saintpetersburg.js
 *
 * SaintPetersburg user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.saintpetersburg", ebg.core.gamegui, {
        constructor: function (){
            this.debug = false; // enabled console logs if true

            if (this.debug) console.log('saintpetersburg constructor');
              
            this.playerHand = null;         // Stock for current player hand
            this.playerTable = null;        // Stock for current player table
            this.cardwidth = 70;            // Standard card width for stock
            this.cardheight = 112;          // Standard card height for stock
            this.card_art_row_size = 10;    // Number of cards per row in sprite for stock
            this.cardwidth_big = 96;        // Large card width for tooltip
            this.cardheight_big = 150;      // Large card height for tooltip
            this.player_rubles = []         // Counters for all player rubles
            this.player_tables = [];        // Stocks for all player tables
            this.player_hands = [];         // Cards held in each player's hand
            this.player_hand_counts = [];   // Counters for all player hands
            this.player_aristocrats = [];   // Counters for all player aristocrats
            this.phases = ['Worker', 'Building', 'Aristocrat', 'Trading']; // Game phases in order
            this.pub_points = 0;            // Current number of points to buy with Pub
            this.max_pub_points = 0;        // Upper limit on Pub points
            this.current_phase = '';        // Current active game phase (string)
            this.card_infos = null;         // Full list of card details
            this.deck_counters = [];        // Counters for cards in each phase stack
                                            // N.B. terms deck and stack are used interchangably
            this.spectator = false;         // Is current player a spectator
            this.client_state_args = {lock:true};    // Object to hold argument during client state changes
            this.possible_moves = null;     // All possible moves for current player
            this.constants = null;          // Constant values between client and server
            this.is_trading = false;        // True if in client state for trading card
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function (gamedatas)
        {
            if (this.debug) console.log("Starting game setup");

            if (this.prefs[100].value == 0) {
                // Show message from publisher player has not seen/acknowledged
                dojo.style('publisher_msg', 'display', 'block');
                dojo.connect($('button_publisher_ack'), 'onclick', this, 'ackPublisherMessage');
            }

            // Auto pass banner
            dojo.connect($('button_cancel_pass'), 'onclick', this, 'onCancelAutoPass');
            if (parseInt(gamedatas.autopass)) {
                // Show banner
                dojo.style('autopass_msg', 'display', '');
            }

            // Store full card details for tooltips
            // Used in game board setup
            this.card_infos = gamedatas.card_infos;
            this.constants = gamedatas.constants;

            // Setting up player boards, tables, cards
            for(var player_id in gamedatas.players) {
                // Custom icons and such
                var player = gamedatas.players[player_id];
                player.url = g_gamethemeurl;
                var player_board_div = $('player_board_' + player_id);
                dojo.place(this.format_block('jstpl_player_board', player), player_board_div);

                // Player hand counters
                var hand_counter = new ebg.counter();
                hand_counter.create('handcount_p' + player_id);
                hand_counter.setValue(gamedatas.player_hand_size[player_id]);
                this.player_hand_counts[player_id] = hand_counter;
                if (gamedatas.player_hands[player_id] && player_id != this.player_id) {
                    // Game option to show player hands enabled
                    // (but no need to do so for current player)
                    this.player_hands[player_id] = [];
                    for (var i in gamedatas.player_hands[player_id]) {
                        this.player_hands[player_id].push(gamedatas.player_hands[player_id][i].type_arg);
                    }
                    this.updateHandTooltip(player_id);
                } else {
                    // Default just show count of cards in hand
                    this.addTooltip('handcount_p' + player_id, _("Number of cards in hand"), "");
                    this.addTooltip('handcount_icon_p' + player_id, _("Number of cards in hand"), "");
                }

                // Player aristocrat counters
                var ari_counter = new ebg.counter();
                ari_counter.create('aricount_p' + player_id);
                ari_counter.setValue(gamedatas.aristocrats[player_id]);
                this.player_aristocrats[player_id] = ari_counter;
                this.addTooltip('aricount_p' + player_id, _("Number of different aristocrats"), "");
                this.addTooltip('aricount_icon_p' + player_id, _("Number of different aristocrats"), "");

                // Player tables and cards
                this.player_tables[player_id] = this.createCardStock('playertable_' + player_id, 0);
                this.player_tables[player_id].onItemCreate = dojo.hitch(this, 'setupNewCard');
                for (var i in gamedatas.player_tables[player_id]) {
                    var card = gamedatas.player_tables[player_id][i];
                    this.player_tables[player_id].addToStockWithId(card.type_arg, card.id);
                }

                // Rubles (default hidden for other players)
                if (gamedatas.rubles[player_id]) {
                    this.player_rubles[player_id] = new ebg.counter();
                    this.player_rubles[player_id].create('rublecount_p' + player_id);
                    this.player_rubles[player_id].setValue(gamedatas.rubles[player_id]);
                    this.addTooltip('rublecount_p' + player_id, _("Number of rubles"), "");
                    this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles"), "");
                } else {
                    this.addTooltip('rublecount_p' + player_id, _("Number of rubles (secret)"), "");
                    this.addTooltip('rublecount_icon_p' + player_id, _("Number of rubles (secret)"), "");
                }
            }

            // Set up player table unless spectating
            this.playerTable = this.player_tables[this.player_id];
            if (this.playerTable === undefined) {
                // Spectator - hide player hand area
                this.spectator = true;
                dojo.style('myhand_wrap', 'display', 'none');
            } else {
                dojo.connect(this.playerTable, 'onChangeSelection', this, 'onPlayerTableSelectionChanged' );
            }

            // Staring player tokens
            this.setTokens(gamedatas.tokens, false);

            // Phase card stacks
            this.setPhase(gamedatas.phase);
            for (var deck in gamedatas.decks) {
                if (deck.startsWith('deck_')) {
                    dojo.connect($(deck), 'onclick', this, 'onClickDeck');
                    var phase = deck.split('_')[1];
                    this.deck_counters[phase] = new ebg.counter();
                    this.deck_counters[phase].create('count_' + phase);
                    this.deck_counters[phase].setValue(gamedatas.decks[deck]);
                    this.setDeckTooltip(phase, gamedatas.decks[deck]);
                }
            }

            // If a stack is empty it will not be included in gamedatas.decks
            // Ensure the elements are created and set cards to zero
            for (var i in this.phases) {
                var phase = this.phases[i];
                if (this.deck_counters[phase] == undefined) {
                    // No counter created means deck is empty
                    dojo.addClass('deck_' + phase, 'stp_emptydeck')
                    dojo.style('count_' + phase, 'color', 'red');
                    this.setDeckTooltip(phase, 0);
                    // Counter shouldn't be needed but create it just in case
                    this.deck_counters[phase] = new ebg.counter();
                    this.deck_counters[phase].create('count_' + phase);
                    this.deck_counters[phase].setValue(0);
                }
            }

            // Player hand
            if (!this.spectator) { // Spectator has no hand element
                this.playerHand = this.createCardStock('myhand', 1);
                this.playerHand.onItemCreate = dojo.hitch(this, 'setupNewCard');
                for (var i in gamedatas.player_hands[this.player_id]) {
                    var card = gamedatas.player_hands[this.player_id][i];
                    this.playerHand.addToStockWithId(card.type_arg, card.id);
                }
                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');
            }
            
            // Game board cards
            for (var i in gamedatas.board_top) {
                var card = gamedatas.board_top[i];
                this.addCardOnBoard(0, card.location_arg, card.type_arg);
            }
            for (var i in gamedatas.board_bottom) {
                var card = gamedatas.board_bottom[i];
                this.addCardOnBoard(1, card.location_arg, card.type_arg);
            }

            // Observatory status
            for (var i in gamedatas.observatory) {
                var card = gamedatas.observatory[i];
                if (card.used == 1) {
                    // Remove link and mask card to show it is used
                    dojo.style('card_content_active_' + card.id, 'display', 'none');
                    dojo.style('card_content_mask_' + card.id, 'display', 'block');
                }
            }

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            if (this.debug) console.log("Ending game setup");
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function (stateName, args)
        {
            if (this.debug) console.log('Entering state: ' + stateName);
            
            switch(stateName)
            {
                case 'playerTurn':
                    this.possible_moves = args.args;
                    this.client_state_args = {lock:true};
                    if (this.isCurrentPlayerActive()) {
                        this.setSelections();
                    }
                    break;
                case 'client_selectCard':
                    this.setSelections();
                    break;
                case 'client_tradeCard':
                    this.is_trading = true;
                    this.setSelections();
                    break;
                case 'client_useObservatory':
                    // Highlight decks for selection
                    dojo.query('.stp_deck').addClass('stp_selectable');
                    break;
                case 'useObservatory':
                    this.possible_moves = {};
                    this.possible_moves[this.constants.observatory] = {};
                    this.possible_moves[this.constants.observatory][0] = args.args;
                    this.client_state_args = {lock:true};
                    this.showObservatoryChoice(args.args);
                    break;
                case 'usePub':
                    // Pub negates auto pass
                    dojo.style('autopass_msg', 'display', 'none');
                    if (args.args[this.player_id] === undefined) {
                        // Should not get here...
                        this.max_pub_points = 0;
                    } else {
                        this.max_pub_points = args.args[this.player_id];
                    }
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function (stateName)
        {
            if (this.debug) console.log('Leaving state: ' + stateName);

            // Reset selections for all items
            // No special handling for any state
            if (!this.spectator) {
                // Spectator has no hand or board!
                this.playerHand.unselectAll();
                this.playerTable.setSelectionMode(0);
            }
            dojo.query('.stp_selected').removeClass('stp_selected');
            dojo.query('.stp_selectable').removeClass('stp_selectable');
            this.is_trading = false;
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function (stateName, args)
        {
            if (this.debug) console.log('onUpdateActionButtons: ' + stateName);
                      
            if(this.isCurrentPlayerActive())
            {            
                switch(stateName)
                {
                    case 'playerTurn':
                        // Options: pass
                        this.addActionButton("button_1", _("Pass"), "onPass");
                        this.addActionButton("button_2", _("Auto pass"), "onAutoPass", null, false, "red");
                        break;
                    case 'client_selectCard':
                        // Options: buy, add, cancel
                        var buy_color = args.can_buy ? "blue" : "gray";
                        var add_color = args.can_add ? "blue" : "gray";
                        this.addActionButton("button_1", _("Buy") + " (" + args.cost + ")", "onBuyCard", null, false, buy_color);
                        this.addActionButton("button_2", _("Add to hand"), "onAddCard", null, false, add_color);
                        this.addActionButton("button_3", _("Cancel"), "onCancelCard", null, false, "red");
                        break;
                    case 'client_tradeCard':
                        // Options: cancel
                        this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                        break;
                    case 'usePub':
                        // Options: -1, +1, buy, pass
                        var color = "blue";
                        if (args[this.player_id] == 0) { // max points player can buy
                            color = "gray";
                        }
                        this.addActionButton("button_1", "-1", "onOneLessPoint", null, false, "gray");
                        this.addActionButton("button_2", "+1", "onOneMorePoint", null, false, color);
                        this.addActionButton("button_3", _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")", "onBuyPoints");
                        this.addActionButton("button_4", _("Pass"), "onBuyNoPoints", null, false, "red");
                        break;
                    case 'client_useObservatory':
                        // Options: cancel
                        this.addActionButton("button_1", _("Cancel"), "onCancelCard", null, false, "red");
                        break;
                    case 'useObservatory':
                        // Options: buy, add, cancel
                        var buy_color = args.can_buy ? "blue" : "gray";
                        var add_color = args.can_add ? "blue" : "gray";
                        this.addActionButton("button_1", _("Buy") + " (" + args.cost + ")", "onBuyCard", null, false, buy_color);
                        this.addActionButton("button_2", _("Add to hand"), "onAddCard", null, false, add_color);
                        this.addActionButton("button_3", _("Discard"), "onDiscardCard");
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        /*
         * Return the div id for the given card location
         */
        getCardDiv: function (row, col)
        {
            return this.getLocationDiv(row, col, 'card');
        },

        /*
         * Return the div id for the given board location
         */
        getBoardDiv: function (row, col)
        {
            return this.getLocationDiv(row, col, 'square');
        },

        getLocationDiv: function (row, col, prefix)
        {
            return prefix + '_' + col + '_' + row;
        },

        /*
         * Return true if button is disabled (grayed-out)
         */
        isButtonDisabled: function (button)
        {
            return dojo.hasClass(button.id, 'bgabutton_gray');
        },

        /*
         * Player clicks okay on note from publisher
         */
        ackPublisherMessage: function (evt)
        {
            // Remove message banner
            dojo.style('publisher_msg', 'display', 'none');
            // Save user preference to not show banner
            // See ly_studio.js
            this.ajaxcall("/table/table/changePreference.html",{id:100,value:1,game:this.game_name}, this, function(){});
        },

        /*
         * Build stock element for player hand and tables
         */
        createCardStock: function (elem, mode)
        {
            var board = new ebg.stock();
            board.create(this, $(elem), this.cardwidth, this.cardheight);
            board.image_items_per_row = this.card_art_row_size;
            for (var i = 0; i < 66; i++) {
                board.addItemType(i, i, g_gamethemeurl+'img/cards.png', i);
            }
            board.setSelectionMode(mode);
            return board;
        },

        /*
         * Create additional content for card elements
         */
        setupNewCard: function (card_div, card_type_id, card_id)
        {
            this.addTooltipHtml(card_div.id, this.getCardTooltip(card_type_id));

            // Observatory is only card needing extra elements
            if (card_type_id == this.constants.observatory && card_div.id.substring(0, 6) != 'myhand') {
                // Get player and card ids to add templated html
                var player_id = parseInt(card_div.id.split('_')[1]);
                var id = card_id.split('_');
                id = id[id.length - 1];
                dojo.place(this.format_block('jstpl_card_content', {id:id}), card_div.id);

                if (player_id == this.player_id) {
                    // Active player, set link text and connect action
                    dojo.query('#card_content_active_' + id + '>a')[0].textContent = _("Activate");
                    dojo.connect(card_div, 'onclick', this, 'onClickObservatory');
                } else {
                    // Other player, no active content
                    dojo.style('card_content_activewrap_' + id, 'display', 'none');
                }
            }
        },

        /*
         * Generate HTML tooltip for given card
         */
        getCardTooltip: function (card_type_id)
        {
            // Get card info and copy to modify
            var card = dojo.clone(this.card_infos[card_type_id]);

            card.card_name = _(card.card_name);

            // Sprite index
            // Image is a bit funky so need to tweak positions
            var x = card_type_id % this.card_art_row_size;
            var y = Math.floor(card_type_id / this.card_art_row_size);
            card.artx = this.cardwidth_big * x + Math.floor(x / 3);
            card.arty = this.cardheight_big * y;
            if (y == 1) card.arty -= 1;

            // card type = <type> [(<worker type> | Trading card [- <worker type>])]
            if (card.card_type == "Worker") {
                card.card_type = _(card.card_type) + " (" + _(card.card_worker_type) + ")";
            } else if (card.card_type == "Trading") {
                card.card_type = _(card.card_trade_type) + " (" + _("Trading card");
                if (card.card_trade_type == "Worker") {
                    card.card_type += " - " + _(card.card_worker_type);
                }
                card.card_type += ")";
            } else {
                card.card_type = _(card.card_type);
            }

            // Number of this card type in game
            card.card_nbr_label = _("Cards in play");

            // Cost and benefits
            var txt = "<p>" + _("Cost") + ": " + card.card_cost + "</p>";
            if (card.card_rubles > 0) {
                txt += "<p>+" + card.card_rubles + " " + _("rubles") + "</p>";
            }
            if (card.card_points > 0) {
                txt += "<p>+" + card.card_points + " " + _("points") + "</p>";
            }

            // Special function text
            if (typeof card.card_text != "undefined") {
                txt += "<p>" + _(card.card_text) + "</p>";
            }
            card.card_text = txt;

            return this.format_block("jstpl_card_tooltip", card);
        },

        /*
         * Update backend tooltip array to move a tooltip from one div to
         * another, for when a card is moved and its id is changed
         */
        resetTooltip: function (old_id, new_id)
        {
            // XXX 
            // This manipulates BGA Tooltip API rather than recreating it from scratch
            // Potentially an issue with furture API changes...
            if (this.tooltips[new_id]) {
                this.tooltips[new_id].destroy();
            }
            this.tooltips[new_id] = this.tooltips[old_id];
            this.tooltips[old_id] = null;
        },

        /*
         * Set the detailed tooltip for player hand to show all cards held
         */
        updateHandTooltip: function (player_id)
        {
            // Sort card by type
            var hand = this.player_hands[player_id];
            hand = hand.sort();

            // Clear all four template cards by default
            var artx = [0, 0, 0, 0];
            var arty = [0, 0, 0, 0];
            var disp = ['none', 'none', 'none', 'none'];

            // Display correct art for each card in hand
            for (var i=0; i<hand.length; i++) {
                artx[i] = this.cardwidth * (hand[i] % this.card_art_row_size);
                arty[i] = this.cardheight * Math.floor(hand[i] / this.card_art_row_size);
                disp[i] = 'inline-block';
            }

            if (hand.length > 0) {
                // Add detailed tooltip
                var html = this.format_block("jstpl_hand_tooltip", {
                    artx: artx,
                    arty: arty,
                    disp: disp,
                    text: _("Cards in player hand") + " (" + hand.length + "):"
                });
                this.addTooltipHtml('handcount_p' + player_id, html)
                this.addTooltipHtml('handcount_icon_p' + player_id, html);
            } else {
                // No cards in hand - use standard tooltip
                this.addTooltip('handcount_p' + player_id, _("Number of cards in hand"), "");
                this.addTooltip('handcount_icon_p' + player_id, _("Number of cards in hand"), "");
            }
        },

        /*
         * Place a card on the game board at the given row,col location.
         * Card specified by its sprite index (idx), source element (src)
         * for slide animation (i.e. card stack)
         */
        addCardOnBoard: function (row, col, idx, src)
        {
            if (src === undefined) {
                src = 'stp_gameboard'
            }

            // Sprite index
            var x = this.cardwidth * (idx % this.card_art_row_size);
            var y = this.cardheight * Math.floor(idx / this.card_art_row_size);

            if (this.debug) console.log('adding card type '+idx+' at x,y '+col+','+row);

            dojo.place(this.format_block('jstpl_card', {
                x:x,
                y:y,
                row: row,
                col: col
            }), 'cards');

            var card_div = this.getCardDiv(row, col);
            this.placeOnObject(card_div, src);
            this.slideToObject(card_div, this.getBoardDiv(row, col)).play();

            this.addTooltipHtml(card_div, this.getCardTooltip(idx));
            dojo.connect($(card_div), 'onclick', this, 'onSelectCard');
        },

        /*
         * Rotate and/or place starting player tokens on player boards
         */
        setTokens: function (tokens, animate)
        {
            var delay = 1000; // 1s animation
            var players = {};

            // Clear existing tokens
            dojo.query('.stp_token_small').removeClass('stp_token_Worker stp_token_Building stp_token_Aristocrat stp_token_Trading');

            // Determine current and next player for each token
            for (var phase in tokens) {
                var token = tokens[phase];
                if (players[token.next]) {
                    // Next player already has one token, use second slot
                    var curr = 'token2_p' + token.current;
                    var next = 'token2_p' + token.next;
                } else {
                    var curr = 'token_p' + token.current;
                    var next = 'token_p' + token.next;
                    players[token.next] = true;
                }

                if (animate) {
                    // Use temp object to show tokens rotating between boards
                    var tmp = '<div id="tmp_token_'+phase+'" class="stp_token_small stp_token_'+phase+'"></div>';
                    this.slideTemporaryObject(tmp, 'token_wrap_p' + token.current, curr, next, delay, 0);
                } else {
                    // Immediately switch token to next player
                    dojo.addClass(next, 'stp_token_' + phase);
                    var txt = _("Starting player for") + " " + _(phase) + " " + _("phase");
                    this.addTooltip(next, txt, "");
                }
            }

            if (animate) {
                // Call this function again without animation to set permenant token icons
                // A bit overly complicated but animation callbacks were not working
                setTimeout(dojo.hitch(this, function() {
                    this.setTokens(tokens, false);
                }), delay);
            }
        },

        /*
         * Rotate card stacks for given phase
         */
        setPhase: function (phase)
        {
            var prev_phase = this.current_phase;
            this.current_phase = phase;
            if (prev_phase != '') {
                // Reset tooltip for previous phase deck
                this.setDeckTooltip(prev_phase, this.deck_counters[prev_phase].getValue());
            }

            // Get platform-specific animation
            var transform;
            dojo.forEach(
                ['transform', 'WebkitTransform', 'msTransform',
                    'MozTransform', 'OTransform'],
                function (name) {
                    if (typeof dojo.body().style[name] != 'undefined') {
                        transform = name;
                    }
                }
            );

            // Card stack for current phase is up and all others rotated 90 deg
            // Rotate all stacks to the correct angle for the given phase
            dojo.forEach(this.phases, function (name) {
                var node = dojo.byId('deck_' + name);
                var curve = [-90, -90]; // no-op

                if (name == phase) {
                    // Current phase -> up, clockwise
                    curve = [-90, 0];
                } else if (node.style[transform] != 'rotate(-90deg)') {
                    // Initial state or previous phase -> 90 anti-clockwise
                    curve = [0, -90];
                }

                // Rotate
                new dojo.Animation({
                    curve: curve,
                    onAnimate: function (v) {
                        node.style[transform] = 'rotate(' + v + 'deg)';
                    }
                }).play();
            });
        },

        /*
         * Highlight selected card and any possible moves
         */
        setSelections: function ()
        {
            if (this.debug) console.log('setSelection');
            if (this.debug) console.log(this.possible_moves);
            if (this.debug) console.log(this.is_trading);

            var row, col, div, card;

            if (this.is_trading) {
                // Player is acting on a trading card
                // Highlight possible trades on table
                row = this.client_state_args.row;
                col = this.client_state_args.col;
                var card_info = this.possible_moves[row][col];

                for (var i in card_info.trades) {
                    div = this.player_tables[this.player_id].getItemDivId(card_info.trades[i]);
                    dojo.addClass(div, 'stp_selectable');
                }

                // Let player select a card on their table
                this.playerTable.setSelectionMode(1);
            } else {
                // Player can select a card to add/buy/play
                // Highlight all possible moves
                for (row in this.possible_moves) {
                    for (col in this.possible_moves[row]) {
                        card = this.possible_moves[row][col];
                        if (card.can_buy || card.can_add) {
                            // Board
                            div = this.getCardDiv(row, col);
                            if (row == this.constants.hand) {
                                // Hand
                                div = this.playerHand.getItemDivId(col);
                            } else if (row == this.constants.observatory) {
                                // Observatory
                                div = this.player_tables[this.player_id].getItemDivId(col);
                            }
                            dojo.addClass(div, 'stp_selectable');
                        }
                    }
                }
            }

            // Highlight currently selected card, if any
            row = this.client_state_args.row;
            col = this.client_state_args.col;
            if (row !== undefined && col !== undefined) {
                if (row == 0 || row == 1) {
                    div = this.getCardDiv(row, col);
                } else if (row == this.constants.hand) {
                    div = this.playerHand.getItemDivId(col);
                } else if (row == this.constants.observatory) {
                    if (col == 0) {
                        // Drawn Observatory card
                        div = this.getCardDiv(this.constants.observatory, col);
                    } else {
                        // Observatory on table
                        div = this.player_tables[this.player_id].getItemDivId(col);
                    }
                }
                if (this.debug) console.log('SELECTED: ' + row + ' ' + col);
                if (this.debug) console.log(div);
                dojo.removeClass(div, 'stp_selectable');
                dojo.addClass(div, 'stp_selected');
            }
        },

        /*
         * Add card drawn with Observatory to middle of board
         */
        showObservatoryChoice: function (args)
        {
            // Sprite index
            var idx = args.card.type_arg;
            var x = this.cardwidth * (idx % this.card_art_row_size);
            var y = this.cardheight * Math.floor(idx / this.card_art_row_size);

            var card_id = this.getCardDiv(this.constants.observatory, 0);
            if ($(card_id)) {
                // Card already exists on board
                // Player must have cancelled last action
                dojo.addClass(card_id, 'stp_selected');
                return;
            }

            this.client_state_args.row = this.constants.observatory;
            this.client_state_args.col = 0;

            // Disable Observatory
            dojo.style('card_content_mask_' + args.obs_id, 'display', 'block');
            dojo.style('card_content_active_' + args.obs_id, 'display', 'none');

            // Remove one card from selected deck
            var num_cards = this.deck_counters[args.card.type].incValue(-1);
            this.setDeckTooltip(args.card.type, num_cards);

            // Place and animate card draw
            dojo.place(this.format_block('jstpl_card', {
                x:x,
                y:y,
                row: this.constants.observatory,
                col: 0
            }), 'cards');
            this.placeOnObject(card_id, 'deck_' + args.card.type);
            dojo.addClass(card_id, 'stp_selected');
            this.slideToObject(card_id, 'stp_gameboard').play();
            this.addTooltipHtml(card_id, this.getCardTooltip(args.card.type_arg));
        },

        /*
         * Generate tooltip for given card stack showing number of cards left
         * [Current phase: ] <phase> stack has <cards> cards
         * OR
         *   "       "         "       "   is empty meaning game will end soon
         */
        setDeckTooltip: function (phase, cards)
        {
            var txt = "";
            if (phase == this.current_phase) {
                // Mark as active phase
                txt += "<b>" + _("Current phase") + ":</b> ";
            }

            txt += _(phase) + " " + _("stack") + " ";

            if (cards == 0) {
                // Special message if stack is empty (end game trigger)
                txt += _("is empty meaning game will end soon");
            } else {
                txt += _("has") + " " + cards + " " + _("cards");
            }

            this.addTooltip('deck_' + phase, txt, "");
        },

        ///////////////////////////////////////////////////
        //// Player's action
        //

        /*
         * Player clicks an active card
         */
        onSelectCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('selectCard'))
                return;

            // Card location
            var coords = evt.currentTarget.id.split('_');
            var col = coords[1];
            var row = coords[2];
            var card_info = this.possible_moves[row][col];

            this.client_state_args.col = col;
            this.client_state_args.row = row;

            this.setClientState('client_selectCard', {
                descriptionmyturn: _('${card_name}: ${you} may buy or add to hand'),
                args: card_info
            });
        },

        /*
         * Player clicks 'Add to hand' button
         */
        onAddCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('addCard'))
                return;

            if (this.isButtonDisabled(evt.target)) {
                this.showMessage(_("Your hand is full"), "error");
                return;
            }

            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/addCard.html",
                this.client_state_args, this, function (result) {});
        },

        /*
         * Player clicks 'Buy' button for card
         */
        onBuyCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('buyCard'))
                return;

            // Get card info to handle trading cards
            var col = this.client_state_args.col;
            var row = this.client_state_args.row;
            var card_info = this.possible_moves[row][col];

            if (this.isButtonDisabled(evt.target)) {
                // Player cannot buy
                // Check if trading card to give most accurate error message
                if (card_info.is_trading && !card_info.has_trade) {
                    this.showMessage(_("You do not have any valid cards to trade"), "error");
                } else {
                    this.showMessage(_("You do not have enough rubles"), "error");
                }
                return;
            }

            if (card_info.is_trading) {
                // Player needs to select card to displace
                this.setClientState('client_tradeCard', {
                    descriptionmyturn: _('${card_name}: ${you} must choose a card to displace (base cost: ${cost})'),
                    args: card_info
                });
            } else {
                // Send buy action to server
                this.ajaxcall(
                    "/saintpetersburg/saintpetersburg/buyCard.html",
                    this.client_state_args, this, function (result) {});
            }
        },

        /*
         * Player clicks 'Cancel' button (several actions)
         */
        onCancelCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('cancel'))
                return;

            // Reset to main state
            this.restoreServerGameState();
        },

        /*
         * Player clicks 'Pass' button (not for Pub)
         */
        onPass: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('pass'))
                return;

            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/pass.html",
                {lock:true}, this, function (result) {});
        },

        /*
         * Player clicks 'Auto pass' button
         */
        onAutoPass: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('pass'))
                return;

            // Turn on warning banner
            dojo.style('autopass_msg', 'display', '');

            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/pass.html",
                {lock:true, auto:true}, this, function (result) {});
        },

        /*
         * Player clicks 'Cancel' button on auto pass banner
         */
        onCancelAutoPass: function (evt)
        {
            dojo.stopEvent(evt);

            // No action check (player is not active)

            // Turn off warning banner
            dojo.style('autopass_msg', 'display', 'none');

            // Do NOT lock interface
            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/cancelPass.html",
                {id:this.player_id}, this, function (result) {});
        },

        /*
         * Player clicks '-1' button for Pub
         */
        onOneLessPoint: function (evt)
        {
            dojo.stopEvent(evt);

            this.pub_points -= 1;

            if (this.pub_points < 0) {
                // Cannot go below zero
                this.showMessage(_("You cannot buy fewer than zero"), "error");
                this.pub_points = 0;
            }

            if (this.pub_points == 0) {
                // "Disable" -1 button
                dojo.removeClass('button_1', 'bgabutton_blue');
                dojo.addClass('button_1', 'bgabutton_gray');
            }

            if (this.pub_points < this.max_pub_points) {
                // "Enable" +1 button
                dojo.removeClass('button_2', 'bgabutton_gray');
                dojo.addClass('button_2', 'bgabutton_blue');
            }

            // Update button text with current points and cost info
            $('button_3').textContent = _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")";
        },

        /*
         * Player clicks '+1' button for Pub
         */
        onOneMorePoint: function (evt)
        {
            dojo.stopEvent(evt);

            this.pub_points += 1;

            if (this.pub_points > this.max_pub_points) {
                // Cannot go above max (provided by server)
                this.showMessage(_("You cannot buy any more points"), "error");
                this.pub_points = this.max_pub_points;
            }

            if (this.pub_points == this.max_pub_points) {
                // "Disable" +1 button
                dojo.removeClass('button_2', 'bgabutton_blue');
                dojo.addClass('button_2', 'bgabutton_gray');
            }

            if (this.pub_points > 0) {
                // "Enable" -1 button
                dojo.removeClass('button_1', 'bgabutton_gray');
                dojo.addClass('button_1', 'bgabutton_blue');
            }

            // Update button text with current points and cost info
            $('button_3').textContent = _("Buy") + " " + this.pub_points + " (" + this.pub_points * 2 + ")";
        },

        /*
         * Player clicks 'Buy' button for Pub
         */
        onBuyPoints: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('buyPoints'))
                return;

            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/buyPoints.html",
                {lock:true, points:this.pub_points}, this, function (result) {});
            this.pub_points = 0;
        },

        /*
         * Player clicks 'Pass' button for Pub
         */
        onBuyNoPoints: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('buyPoints'))
                return;

            // Buy zero points
            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/buyPoints.html",
                {lock:true, points:0}, this, function (result) {});
            this.pub_points = 0;
        },

        /*
         * Player clicks a card in their hand
         */
        onPlayerHandSelectionChanged: function ()
        {
            var items = this.playerHand.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('playCard')) {
                    // Play card from hand
                    var card_id = items[0].id;
                    this.client_state_args.col = card_id;
                    this.client_state_args.row = this.constants.hand;

                    var card = this.possible_moves[this.constants.hand][card_id];
                    if (card === undefined) {
                        alert("Unexpected error playing card from hand");
                    }

                    if (!card.can_buy) {
                        // Player cannot play this card
                        // Check if trading card to give most accurate error message
                        if (card.is_trading && !card.has_trade) {
                            this.showMessage(_("You do not have any valid cards to trade"), "error");
                        } else {
                            this.showMessage(_("You do not have enough rubles"), "error");
                        }
                        this.playerHand.unselectAll();
                        return;
                    }
                    
                    if (card.is_trading) {
                        // Player needs to select card to displace
                        this.setClientState('client_tradeCard', {
                            descriptionmyturn: _('${card_name}: ${you} must choose a card to displace (base cost: ${cost})'),
                            args: card
                        });
                    } else {
                        // Send play action to server
                        this.ajaxcall(
                            "/saintpetersburg/saintpetersburg/playCard.html",
                            this.client_state_args, this, function (result) {});
                    }

                    this.playerHand.unselectAll();
                } else {
                    // Cannot play from hand right now
                    this.playerHand.unselectAll();
                }                
            }
        },
        
        /*
         * Player clicks a card on their table
         */
        onPlayerTableSelectionChanged: function ()
        {
            var items = this.playerTable.getSelectedItems();

            if (items.length > 0) {
                if (this.checkAction('buyCard') && this.is_trading) {
                    // Displace card with trading card
                    this.client_state_args.trade_id = items[0].id;
                    
                    if (this.client_state_args.row == this.constants.hand) {
                        // Play from hand
                        this.ajaxcall(
                            "/saintpetersburg/saintpetersburg/playCard.html",
                            this.client_state_args, this, function (result) {});
                    } else {
                        // Buy from board
                        this.ajaxcall(
                            "/saintpetersburg/saintpetersburg/buyCard.html",
                            this.client_state_args, this, function (result) {});
                    }

                    this.playerTable.unselectAll();
                } else {
                    // Cannot trade cards right now
                    this.showMessage(_("You must select first select a card to buy"), "error");
                    this.playerTable.unselectAll();
                }                
            }
        },

        /*
         * Player clicks Observatory on their board
         */
        onClickObservatory: function (evt)
        {
            dojo.stopEvent(evt);

            if (this.is_trading) {
                // In trade state
                // Do not register click and let state machine handle the rest
                return;
            }

            if (!this.checkAction('useObservatory'))
                return;

            if (this.current_phase != this.phases[1]) {
                // Not building phase, can't use
                this.showMessage(_("You can only use the Observatory during the Building phase"), "error");
                return;
            }

            var obs_id = evt.currentTarget.id.split('_')[3];
            if (this.client_state_args.obs_id == obs_id) {
                // Already in client state for Observatory
                // Player needs to click choose a deck or cancel
                this.showMessage(_("You must select a card stack on the board"), "error");
                return;
            }

            this.client_state_args.obs_id = obs_id;

            this.setClientState('client_useObservatory', {
                descriptionmyturn: _('Observatory: ${you} must choose a stack to draw from')
            });
        },

        /*
         * Player clicks a card stack
         */
        onClickDeck: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('useObservatory', true)) {
                // Decks are only selectable and active after using Observatory
                // Ignore click without error message
                return;
            }

            this.client_state_args.deck = evt.currentTarget.id;
            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/drawObservatoryCard.html",
                this.client_state_args, this, function (result) {});
        },

        /*
         * Player clicks 'Discard' button for Observatory
         */
        onDiscardCard: function (evt)
        {
            dojo.stopEvent(evt);
            if (!this.checkAction('discard'))
                return;

            this.ajaxcall(
                "/saintpetersburg/saintpetersburg/discardCard.html",
                {lock:true}, this, function (result) {});
        },

        

        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your saintpetersburg.game.php file.
        
        */
        setupNotifications: function ()
        {
            if (this.debug) console.log('notifications subscriptions setup');
            
            dojo.subscribe('buyCard', this, 'notif_buyCard');
            dojo.subscribe('addCard', this, 'notif_addCard');
            dojo.subscribe('playCard', this, 'notif_playCard');
            dojo.subscribe('shiftRight', this, 'notif_shiftRight');
            this.notifqueue.setSynchronous('shiftRight', 1000);
            dojo.subscribe('shiftDown', this, 'notif_shiftDown');
            this.notifqueue.setSynchronous('shiftDown', 500);
            dojo.subscribe('discard', this, 'notif_discard');
            this.notifqueue.setSynchronous('discard', 1000);
            dojo.subscribe('scorePhase', this, 'notif_scorePhase');
            dojo.subscribe('nextPhase', this, 'notif_nextPhase');
            this.notifqueue.setSynchronous('nextPhase', 1000);
            dojo.subscribe('newScores', this, 'notif_newScores');
            dojo.subscribe('lastRound', this, 'notif_lastRound');
            dojo.subscribe('newRound', this, 'notif_newRound');
            dojo.subscribe('buyPoints', this, 'notif_buyPoints');
        },  
        
        /*
         * Message for player buying card
         */
        notif_buyCard: function (notif)
        {
            if (this.debug) console.log('buy card notif');
            if (this.debug) console.log(notif);

            // Card position on board
            var row = notif.args.card_row;
            var col = notif.args.card_loc;
            var src = this.getBoardDiv(row, col);

            if (row == this.constants.observatory) {
                // Observatory pick
                col = 0;
                src = 'stp_gameboard';
            }

            if (notif.args.trade_id > 0) {
                // Remove displaced card from table
                this.player_tables[notif.args.player_id].removeFromStockById(
                    notif.args.trade_id, 'discard_pile');
            }

            // Move card from board to player table
            dojo.destroy('card_' + col + '_' + row);
            this.player_tables[notif.args.player_id].addToStockWithId(
                notif.args.card_idx, notif.args.card_id, src);

            if (this.player_rubles[notif.args.player_id]) {
                // Active player sees ruble count after playing cost
                // (either their own or with game option enabled for others)
                this.player_rubles[notif.args.player_id].incValue(-notif.args.card_cost);
            }

            this.player_aristocrats[notif.args.player_id].setValue(notif.args.aristocrats);
        },

        /*
         * Message for player adding card to their hand
         */
        notif_addCard: function (notif)
        {
            if (this.debug) console.log('add card notif');
            if (this.debug) console.log(notif);

            // Card position on board
            var row = notif.args.card_row;
            var col = notif.args.card_loc;
            var src = this.getBoardDiv(row, col);

            if (row == this.constants.observatory) {
                // Observatory pick
                col = 0;
                src = 'stp_gameboard';
            }

            if (this.player_id == notif.args.player_id) {
                // Active player - add card to hand
                dojo.destroy(this.getCardDiv(row, col));
                this.playerHand.addToStockWithId(
                    notif.args.card_idx, notif.args.card_id, src);
            } else {
                // Other player - move card to player board and destroy
                var anim = this.slideToObject(this.getCardDiv(row, col),
                    'player_board_' + notif.args.player_id);
                dojo.connect(anim, 'onEnd', function (node) {
                    dojo.destroy(node);
                });
                anim.play();
            }

            // Update hand count on player board
            this.player_hand_counts[notif.args.player_id].incValue(1);

            // Update hand tooltip if show cards option enabled
            if (this.player_hands[notif.args.player_id]) {
                this.player_hands[notif.args.player_id].push(notif.args.card_idx);
                this.updateHandTooltip(notif.args.player_id);
            }
        },

        /*
         * Message for player playing card from their hand
         */
        notif_playCard: function (notif)
        {
            if (this.debug) console.log('buy card notif');
            if (this.debug) console.log(notif);

            if (notif.args.trade_id > 0) {
                // Remove displaced card from table
                this.player_tables[notif.args.player_id].removeFromStockById(
                    notif.args.trade_id, 'discard_pile');
            }

            if (notif.args.player_id == this.player_id) {
                // Active player - move card from hand to table
                this.playerTable.addToStockWithId(
                    notif.args.card_idx, notif.args.card_id,
                    'myhand_item_' + notif.args.card_id);
                this.playerHand.removeFromStockById(notif.args.card_id);
            } else {
                // Other players - add card to table
                this.player_tables[notif.args.player_id].addToStockWithId(
                    notif.args.card_idx, notif.args.card_id,
                    'overall_player_board_' + notif.args.player_id);
            }

            if (this.player_rubles[notif.args.player_id]) {
                // Active player sees ruble count after playing cost
                // (either their own or with game option enabled for others)
                this.player_rubles[notif.args.player_id].incValue(-notif.args.card_cost);
            }

            // Update hand count on player board
            this.player_hand_counts[notif.args.player_id].incValue(-1);

            this.player_aristocrats[notif.args.player_id].setValue(notif.args.aristocrats);

            // Update hand tooltip if show cards option enabled
            if (this.player_hands[notif.args.player_id]) {
                var idx = this.player_hands[notif.args.player_id].indexOf(notif.args.card_idx);
                this.player_hands[notif.args.player_id].splice(idx, 1);
                this.updateHandTooltip(notif.args.player_id);
            }
        },

        /*
         * Move all cards on board to right-most positions
         */
        notif_shiftRight: function (notif)
        {
            if (this.debug) console.log('notif shift right');
            if (this.debug) console.log(notif);

            var row = notif.args.row;
            for (var i in notif.args.columns) {
                var old_col = i;
                var new_col = notif.args.columns[i];
                if (new_col != old_col) {
                    var old_card = this.getCardDiv(row, old_col);
                    var new_card = this.getCardDiv(row, new_col);
                    // Slide card right to new position
                    this.slideToObject(old_card, this.getBoardDiv(row, new_col)).play();
                    // Update card DOM id for new position
                    dojo.attr(old_card, 'id', new_card);
                    this.resetTooltip(old_card, new_card);
                }
            }

        },

        /*
         * Move all cards on board from upper to lower row
         */
        notif_shiftDown: function (notif)
        {
            if (this.debug) console.log('notif shift down');
            if (this.debug) console.log(notif);

            for (var i in notif.args.columns) {
                var col = notif.args.columns[i];
                var old_card = this.getCardDiv(0, col);
                var new_card = this.getCardDiv(1, col);
                // Slide card down to new position
                this.slideToObject(old_card, this.getBoardDiv(1, col)).play();
                // Update card DOM id for new position
                dojo.attr(old_card, 'id', new_card);
                this.resetTooltip(old_card, new_card);
            }
        },

        /*
         * Message for each player's end of phase scoring
         */
        notif_scorePhase: function (notif)
        {
            if (this.debug) console.log('notif score phase');
            if (this.debug) console.log(notif);

            if (this.player_rubles[notif.args.player_id]) {
                // Active player sees ruble count after playing cost
                // (either their own or with game option enabled for others)
                this.player_rubles[notif.args.player_id].incValue(notif.args.rubles);
            }
        },

        /*
         * Message for new phase starting
         */
        notif_nextPhase: function (notif)
        {
            if (this.debug) console.log('notif next phase');
            if (this.debug) console.log(notif);

            // Remove auto pass banners
            dojo.style('autopass_msg', 'display', 'none');

            // Rotate card stacks
            this.setPhase(notif.args.phase);
            var deck = 'deck_' + notif.args.phase;

            // Draw new cards onto board
            var draw = 0;
            for (var i in notif.args.cards) {
                this.addCardOnBoard(0, i, notif.args.cards[i], deck);
                draw++;
            }

            // TODO: Possible issue where deck counter value goes too low?
            // Noticed in one game (off by one) and not yet reproduced
            // Update deck counters and tooltips
            var num_cards = this.deck_counters[notif.args.phase].incValue(-draw);
            this.setDeckTooltip(notif.args.phase, num_cards);
            if (num_cards == 0) {
                // Highlight that stack is empty and game is in end state
                dojo.addClass(deck, 'stp_emptydeck')
                dojo.style('count_' + notif.args.phase, 'color', 'red');
            }
        },

        /*
         * Message for all updated player scores
         */
        notif_newScores: function (notif)
        {
            if (this.debug) console.log('notif new scores');
            if (this.debug) console.log(notif);

            for(var player_id in notif.args.scores)
            {
                var newScore = notif.args.scores[player_id];
                this.scoreCtrl[player_id].toValue(newScore);
            }
        },

        /*
         * Message for discarding cards from board either
         * from new round or discarded Observatory draw
         */
        notif_discard: function (notif)
        {
            if (this.debug) console.log('notif discard');
            if (this.debug) console.log(notif);

            for (var i in notif.args.cards) {
                var card = notif.args.cards[i];
                // Card location
                var row = card.row;
                var col = card.col;
                if (row == this.constants.observatory) {
                    // Observatory pick
                    var col = 0;
                }

                // Move to discard pile and destroy
                var anim = this.slideToObject(this.getCardDiv(row, col), 'discard_pile');
                dojo.connect(anim, 'onEnd', function (node) {
                    dojo.destroy(node);
                });
                anim.play();
            }
        },

        /*
         * Message for new round starting
         */
        notif_newRound: function (notif)
        {
            if (this.debug) console.log('notif new round');
            if (this.debug) console.log(notif);

            // Animate rotation of starting player tokens
            this.setTokens(notif.args.tokens, true);

            // Reset Observatory cards to be usable
            dojo.query('.stp_maskcard').style('display', 'none');
            dojo.query('.stp_activecard').style('display', 'block');
        },

        /*
         * Message for player buying points with Pub
         */
        notif_buyPoints: function (notif)
        {
            if (this.debug) console.log('notif buy points');
            if (this.debug) console.log(notif);

            // Update score
            this.scoreCtrl[notif.args.player_id].incValue(notif.args.points);

            if (this.player_rubles[notif.args.player_id]) {
                // Active player sees ruble count after playing cost
                // (either their own or with game option enabled for others)
                this.player_rubles[notif.args.player_id].incValue(-notif.args.cost);
            }
        },

        /*
         * Message for end of game starting
         */
        notif_lastRound: function (notif)
        {
            // In addition to log show message in game window
            this.showMessage(_('This is now the final round!'), 'info');
        },
        
   });             
});
