
The official Wedge plugin repository.
=====================================

Plugins are packages that can extend your forum software by adding a feature, removing another,
or modifying your experience. They're written in a way that they can't overwrite core files,
and instead have to 'hook' into specific entry points in the codebase to do their magic.

I'm considering adding the ability to modify core files, BUT with a twist that will make it
impossible to break your forum. Stay tuned for more details.

Installing a plugin from this repo.
-----------------------------------

Go to the `github.com/Wedge/plugins` repository.

Click the 'Download ZIP' button. This will download all plugins at once. (Don't worry, it's
a quick download.) You could also download plugin folders separately, but GitHub doesn't, AFAIK,
offer the ability to download them easily as a zip file.

Unzip the file, and upload any folders you like to your FTP account, into the `/plugins/` folder
at the root of your forum folder.

Well... That's pretty much it. Now go to the Admin area, and look into the Plugins menu.
You'll see a list of the plugins you uploaded, and you can click the big buttons next to each
plugin to Enable them, Disable them or Set them up. Yes, I don't know why I capitalized these
names either.

Some plugins of interest.
-------------------------

- `ajax_qr` is an Ajax Quick Reply plugin. Not thoroughly tested.
- `birthday` and `calendar` are SMF features which were ported to plugins.
- `edit_history` puts a bit of wiki into your posts. No more ninja edits!
- `mass_attach` (as used on wedge.org) allows you to add (and upload) multiple attachments at once by drag'n'drop.
- `mentions` is another popular wedge.org plugin, allowing you to mention a @user and draw their attention.
- `skin_selector` is a must-use plugin. It adds a sidebar box that lets you quickly switch between skins.
- `topic_solved` allows you to turn a board into a basic helpdesk, where users can mark their topics as solved or not.
- `users_online_today` adds a sidebar box with a list of all members who passed by your forum today.
- `wedgedesk` is the only broken plugin. It's completely untested, and will probably remain this way.
- `word_limits` allows you to add moderation filters preventing users from posting messages that are either too short, or too long.

All plugins have a more thorough description, along with author credits, in their respective `plugin-info.xml' files.

Can you read me my rights?
--------------------------

Read license.txt, and if you don't get all of the legalese, it just means:
- Currently, Wedge is free of charge, but it's not free to redistribute. As such, it's
  not 'free and open source' software, but it's definitely open source.
- You can't redistribute any package, plugin or folder by yourself. If you ever find yourself in the need
  to do so, drop me a PM at wedge.org (user name is Nao, it's easy, the guy's everywhere.)
- And other details that most people shouldn't have to bother with. Still, be respectful. Thanks.

-- René-Gilles Deberdt (Nao).
