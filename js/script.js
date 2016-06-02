/* global bp, BP_Reactions */
window.bp  = window.bp  || {};

( function( exports, $ ) {
	if ( 'undefined' === typeof BP_Reactions ) {
		return;
	}

	bp.react = {
		start: function() {
			// Display the reactions
			$( '#buddypress .activity' ).on( 'click', '.activity-list .activity-meta .button.react', this.appendReactions );

			// Edit the reactions
			$( '#buddypress .activity' ).on( 'click', '.activity-list .activity-reactions a', this.saveReaction );
		},

		ajax: function( post_data, type ) {
			if ( this.ajax_request ) {
				this.ajax_request.abort();
			}

			// Extend posted data with stored data and object nonce
			$.extend( post_data, { nonce: BP_Reactions.nonces[type] } );

			this.ajax_request = $.post( BP_Reactions.ajaxurl, post_data, 'json' );

			return this.ajax_request;
		},

		warning: function( message, type, element ) {
			var output = $( '<div></div>' ).html( '<p>' + String.fromCodePoint( '0x1F631' ) + ' ' + message + '</p>' ).addClass( type ).prop( 'id', 'message' );

			element.append( output );

			output.fadeOut( 4000, function() {
				$( this ).remove();
			} );
		},

		appendReactions: function( event ) {
			event.preventDefault();

			// Only logged in users can react!
			if ( ! BP_Reactions.is_user_logged_in ) {
				return;
			}

			var activityID = $( event.currentTarget ).data( 'bp-activity-id' ),
			    $reactions = $( 'li#activity-' + activityID  ).find( '.activity-reactions' );

			if ( $( event.currentTarget ).hasClass( 'open' ) ) {
				$( event.currentTarget ).removeClass( 'open' );
				$reactions.removeClass( 'active' );
				return;
			}

			$( event.currentTarget ).addClass( 'open' );

			if ( 0 !== $reactions.html().length ) {
				$reactions.addClass( 'active' );
				return;
			}

			var postdata = {
				action: 'bp_activity_reactions_fetch',
				activity_id: activityID
			};

			bp.react.ajax( postdata, 'fetch' ).done( function( response ) {
				$reactions.addClass( 'active' );

				if ( false === response.success ) {
					bp.react.warning( response.data.message, 'error', $reactions );
					return;
				}

				$.each( response.data, function( key, reaction ) {
					var classes = '';

					if ( reaction.reacted ) {
						classes = 'reacted';
					}

					$reactions.append(
						$( '<a></a>' ).html(
							String.fromCodePoint( reaction.emoji ) + '<span>' + reaction.count + '</span>'
						).addClass( classes ).attr( 'data-bp-reaction-id', key ).prop( 'title', BP_Reactions.reaction_labels[ key ] )
					);
				} );
			} );
		},

		saveReaction: function( event ) {
			event.preventDefault();

			var $emojiButton = $( event.currentTarget ), emojiLink = $.parseHTML( $( event.currentTarget ).html() ), $spanEmoji,
				$reactButton = $emojiButton.closest( 'li' ).find( '.react' ), $spanReact,
				reactHtml    = $.parseHTML( $reactButton.html() ),
				newSpanEmoji = '', newSpanReact = '';

			var postdata = {
				action: 'bp_activity_reactions_save',
				activity_id: $reactButton.data( 'bp-activity-id' ),
				doaction: $emojiButton.hasClass( 'reacted' ) ? 'remove' : 'add',
				reaction: $emojiButton.data( 'bp-reaction-id' )
			};

			bp.react.ajax( postdata, 'save' ).done( function( response ) {
				if ( false === response.success ) {
					bp.react.warning( response.data.message, 'error', $emojiButton.parent() );
					return;
				}

				var result = 1;

				if ( postdata.doaction === 'remove' ) {
					result = -1;
					$emojiButton.removeClass( 'reacted' );
				} else {
					$emojiButton.addClass( 'reacted' );
				}

				// Update Count for the emoji
				$.each( emojiLink, function( i, el ){
					if ( 'SPAN' === el.nodeName ) {
						$spanEmoji   = '<span>' + el.innerHTML + '</span>';
						newSpanEmoji = '<span>' + Number( parseInt( el.innerHTML, 10 ) + result ) + '</span>';
					}
				} );

				if ( '' !== newSpanEmoji ) {
					$emojiButton.html( $emojiButton.html().replace( $spanEmoji, newSpanEmoji ) );
				}

				// Update Count for the react button
				$.each( reactHtml, function( i, el ){
					if ( 'SPAN' === el.nodeName ) {
						$spanReact   = '<span>' + el.innerHTML + '</span>';
						newSpanReact = '<span>' + Number( parseInt( el.innerHTML, 10 ) + result ) + '</span>';
					}
				} );

				if ( '' !== newSpanReact ) {
					$reactButton.html( $reactButton.html().replace( $spanReact, newSpanReact ) );
				}

				if ( 'undefined' !== typeof BP_Reactions.user_scope ) {
					// If on the member's reactions screen, eventually remove entries
					if ( $( '#activity-' + BP_Reactions.user_scope + '-personal-li' ).length && $( '#activity-' + BP_Reactions.user_scope + '-personal-li' ).hasClass( 'selected' ) ) {
						if ( ( 'reactions' === BP_Reactions.user_scope && ! $emojiButton.parent().find( '.reacted' ).length ) || BP_Reactions.user_scope === postdata.reaction ) {
							$emojiButton.closest( 'li' ).remove();
						}
					}
				}

				// If on the popular directory tab, eventually remove entries
				if ( $( '#activity-popular' ).length && $( '#activity-popular' ).hasClass( 'selected' ) ) {
					if ( '<span>0</span>' === newSpanReact ) {
						$emojiButton.closest( 'li' ).remove();
					}
				}
			} );
		}
	};

	/**
	 * Autocomplete for Emojis
	 */
	$( '.bp-suggestions' ).atwho( {
		at: ':',
		tpl: '<li data-value="${id}">${name} <span class="bp-reactions-emoji">${id}</span></li>',
		data: $.map( BP_Reactions.emojis, function( value ) {
			return { 'name': value.name, 'id': String.fromCodePoint( value.id ) };
		} ),
		limit: 10
	} );

	bp.react.start();

} )( bp, jQuery );
