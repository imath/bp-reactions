=== BP Reactions ===
Contributors: imath
Donate link: http://imathi.eu/donations/
Tags: BuddyPress, Reactions, Emoji
Requires at least: 4.5
Tested up to: 4.5.2
Stable tag: 1.0.1
License: GPLv2

React to BuddyPress activities!

== Description ==

BP Reactions is a BuddyPress (2.5+) plugin, requiring the Activity component to be active, that will allow your members to:

* easily add emoji to the content of their activities thanks to an autocomplete feature (eg: type :heart for the heart emoji),
* react to posted activities.

Built in reactions are "favorites" and "likes". The "favorites" one is replacing the BuddyPress Activity favorites feature by default, but you have the choice to keep BuddyPress favorites thanks to an option you can set into the BuddyPress settings screen.
Plugin comes with an API to let developers add their custom reactions, the ones that will suit best in their community :)

https://vimeo.com/166707145

== Installation ==

You can download and install BP Reactions using the built in WordPress plugin installer. If you download BP Reactions manually, make sure it is uploaded to "/wp-content/plugins/bp-reactions/".

Activate BP Reactions in the "Plugins" admin panel using the "Network Activate" if you activated BuddyPress on the network (or "Activate" if you are not running a network, or if BuddyPress is activated on a subsite of the network) link.

== Frequently Asked Questions ==

= Is it possible to migrate BuddyPress favorites to the favorite reactions ? =
If you choose to let BP Reactions manage users favorites, you can migrate their favorites from the BuddyPress Tools Administration screen. Beneath the Repair tools, you will find a Migrate tool. Simply activate the checkbox to migrate the favorites and hit the "Migrate Items" button.

= How can i add custom reactions ? =
The video on the main description page of the plugin is explaining how to do it (timeline 2:25min to 3:44min). You can add your custom reactions within a bp-custom.php file. Here's a [code example](https://gist.github.com/imath/8b100a78c6fef5807e31c64c8e5eab17 "code example") for a recommandation reaction.

= If you have any other questions =

Please add a comment <a href="http://imathi.eu/tag/bp-reactions/">here</a>

== Screenshots ==

1. The emoji autocomplete Activity Post Form.
2. React button & the Activity Directory Popular tab.

== Changelog ==

= 1.0.1 =
* Make sure hidden activities are not reacted twice on the same type of reaction.
* Improve the layout of the reactions horizontal bar for the single activity screen.

= 1.0.0 =
* Requires BuddyPress 2.5.
* Requires the BuddyPress Activity Component to be active.
* Adds an autocomplete to the Activity Post Form to easily find emoji.
* Adds a button to activity entries to allow people to react to them.
* Includes the "Favorite" and "Like" reactions.
* An API allows developer to add custom reactions.

== Upgrade Notice ==

= 1.0.1 =
Nothing particular, but as usual, back-up your database.

= 1.0.0 =
first version of the plugin, so nothing particular.
