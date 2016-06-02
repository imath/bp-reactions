/* global wp, bpReactions, BP_Reaction_Migrate */
window.wp = window.wp || {};
window.bpReactions = window.bpReactions || {};

( function( exports, $ ) {

	if ( typeof BP_Reaction_Migrate === 'undefined' ) {
		return;
	}

	_.extend( bpReactions, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	// Init Models and Collections
	bpReactions.Models      = bpReactions.Models || {};
	bpReactions.Collections = bpReactions.Collections || {};

	// Init Views
	bpReactions.Views = bpReactions.Views || {};

	/**
	 * The Migrator!
	 */
	bpReactions.Tool = {
		/**
		 * Launcher
		 */
		start: function() {
			this.tasks     = new bpReactions.Collections.Tasks();
			this.step      = 0;
			this.completed = false;

			// Create the task list view
			var task_list = new bpReactions.Views.Migrator( { collection: this.tasks } );

			task_list.inject( '#bp-reactions-migrates' );

			this.setUpTasks();
		},

		/**
		 * Populate the tasks collection
		 */
		setUpTasks: function() {
			var self = this;

			_.each( $( '#bp-reactions-migrate input[type=checkbox]:checked' ), function( task, index ) {
				self.tasks.add( {
					id      : $( task ).prop( 'id' ),
					order   : index,
					message : $( task ).data( 'message' ),
					count   : $( task ).val(),
					number  : $( task ).data( 'number' ),
					done    : 0,
					active  : false
				} );
			} );
		}
	};

	/**
	 * The Tasks collection
	 */
	bpReactions.Collections.Tasks = Backbone.Collection.extend( {
		proceed: function( options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};

			options.data = _.extend( options.data, {
				action : 'bp_reactions_migrate',
				nonce  : BP_Reaction_Migrate.nonce
			} );

			return bpReactions.ajax.send( options );
		}
	} );

	/**
	 * Extend Backbone.View with .prepare() and .inject()
	 */
	bpReactions.View = bpReactions.Backbone.View.extend( {
		inject: function( selector ) {
			this.render();
			$( selector ).html( this.el );
			this.views.ready();
		},

		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	/**
	 * List of tasks view
	 */
	bpReactions.Views.Migrator = bpReactions.View.extend( {
		tagName   : 'div',

		initialize: function() {
			this.views.add( new bpReactions.View( { tagName: 'ul', id: 'bp-reactions-migrate-tasks' } ) );

			this.collection.on( 'add', this.injectTask, this );
			this.collection.on( 'change:active', this.manageQueue, this );
			this.collection.on( 'change:done', this.manageQueue, this );
		},

		taskSuccess: function( response ) {
			var task, next, nextTask;

			if ( response.done && response.callback ) {
				task = this.get( response.callback );

				task.set( 'done', Number( response.done ) + Number( task.get( 'done' ) ) );
				bpReactions.Tool.step += Number( task.get( 'number' ) );

				if ( Number( task.get( 'count' ) ) === Number( task.get( 'done' ) ) ) {
					$( '#' + response.callback + ' .bp-reactions-progress' ).html( BP_Reaction_Migrate.success ).addClass( 'updated' );

					task.set( 'active', false );
					bpReactions.Tool.step = 0;

					next     = Number( task.get( 'order' ) ) + 1;
					nextTask = this.findWhere( { order: next } );

					if ( _.isObject( nextTask ) ) {
						nextTask.set( 'active', true );
					}
				}
			}
		},

		taskError: function( response ) {
			if ( response.message && response.callback ) {
				$( '#' + response.callback + ' .bp-reactions-progress' ).html( response.message ).addClass( response.type );
			}
		},

		injectTask: function( task ) {
			this.views.add( '#bp-reactions-migrate-tasks', new bpReactions.Views.Task( { model: task } ) );
		},

		manageQueue: function( task ) {
			if ( true === task.get( 'active' ) ) {
				this.collection.proceed( {
					data    : _.extend( _.pick( task.attributes, ['id', 'count', 'number', 'done'] ), { step: bpReactions.Tool.step } ),
					success : this.taskSuccess,
					error   : this.taskError
				} );
			}
		}
	} );

	/**
	 * The task view
	 */
	bpReactions.Views.Task = bpReactions.View.extend( {
		tagName   : 'li',
		template  : bpReactions.template( 'progress-window' ),
		className : 'bp-reactions-migrate-task',

		initialize: function() {
			this.model.on( 'change:done', this.taskProgress, this );
			this.model.on( 'change:active', this.addClass, this );

			if ( 0 === this.model.get( 'order' ) ) {
				this.model.set( 'active', true );
			}
		},

		addClass: function( task ) {
			if ( true === task.get( 'active' ) ) {
				$( this.$el ).addClass( 'active' );
			}
		},

		taskProgress: function( task ) {
			if ( ! _.isUndefined( task.get( 'done' ) ) && ! _.isUndefined( task.get( 'count' ) ) ) {
				var percent = ( Number( task.get( 'done' ) ) / Number( task.get( 'count' ) ) ) * 100;
				$( '#' + task.get( 'id' ) + ' .bp-reactions-progress .bp-reactions-bar' ).css( 'width', percent + '%' );
			}
		}
	} );

	$( '#bp-reactions-migrate-submit' ).on( 'click', function( event ) {
		event.preventDefault();

		if ( ! $( '#bp-reactions-migrate input[type=checkbox]:checked' ).length ) {
			window.alert( BP_Reaction_Migrate.notask );
			return;
		}

		bpReactions.Tool.start();
	} );


} )( bpReactions, jQuery );
