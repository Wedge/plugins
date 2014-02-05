Hello everyone, I'm Nao. I'm the author of Wedge. I think you've heard about that one.

I also made this tiny little plugin that interfaces with your GitHub repositories, and reposts any commits you make to a topic of your choice.

Did you write some plugin for Wedge, just like I did today? Happy with it? Updating it a lot? Want to post your commits to your topics? You can do it now.

First of all, enable the plugin. I assure you, it's easy. There's a big On button, just press it. Voilà, c'est bien.

Now you're in the GitHub admin area. We'll need you to fill in three entries. We'll do this from bottom to top, just because.
[list]
[li][b]Repo list[/b]: the... esoteric one. You'll have to provide a list of repositories that you want to repost to your forum, one per line, followed by a colon and the target topic's ID for each repository. For instance, [tt]MyAccount/MyRepo:1234[/tt].[/li]
[li][b]Secret key[/b]: this one is important. Because any silly script can guess your plugin's URL and impersonate GitHub, there are two ways to prevent that. Either ensure the requests are sent from GitHub's IP address list (they provide one), or make the callback URL more complicated. I chose the second solution because it's more secure, but a combination of both could be implemented in the future. Choose a secret password then, something that can be copied into a URL without any problems, so avoid funky characters like ':' or '#'. In fact, just stick to alphanumeric (a-z, A-Z and 0-9), and just make it long. THIS-could-BE-a-GOOD-secret-KEY-but-HACKERS-are-BOUND-to-TRY-it, for instance. Don't choose that one, okay?[/li]
[li][b]Token[/b]: this is optional. If you or your team is posting more than a total of 60 commits per hour on your repository list[nb]Which in itself is already a bit much, but I'm not judging.[/nb], GitHub might not be happy with the extra load. Go to your GitHub account, and generate a secure authentication token. Then paste it here. You'll now be able to access 5,000 commits per hour before they start yelling at you. Good.[/li]
[/list]
I'll give you the repo list I'm using on Wedge.org, because it's perfectly secure:

[tt]Wedge/wedge:6108
Wedge/languages:8337
Wedge/importer:8350
Wedge/plugins:7473[/tt]

That is, in order:
- Anytime someone commits to https://github.com/Wedge/wedge, repost it to wedge.org/?topic=6108, the infamous 'New Revs' topic.
- When they post to Wedge/languages, repost to topic #8337, which is called 'Language Revs', very appropriately.
- If they post to the importer repo, then it'll be reposted immediately to topic #8350, 'Importer tool'.
- And finally, any commits pushed to the official plugin repo gets reposted to 'Plugin Revs'.

It's nothing complicated.
Just hit Submit and save.

ONE LAST STEP, though!

Now, go to your GitHub account. See the list of repos you want to repost to your forum? You'll have to visit them one by one.
Go to the repo's homepage, then click on [b]Settings[/b], then click [b]Service Hooks[/b], then click [b]WebHook URLs[/b] (first choice in the list, normally.)
You're now presented with an input box where you'll need to enter your callback URL.

The URL in question should be something like:
[tt][nobbc]http://my-forum.com/plugins/github/github.php?key=MY-SECRET-KEY-FROM-BEFORE[/nobbc][/tt]

Replace my-forum.com with your proper forum URL, of course. The GitHub admin page should give you the proper URL, and you can copy the secret key from that page as well.
Now, just keep that full URL in your clipboard, submit your WebHook, then proceed to the next repository, where you'll follow the same instructions, and enter exactly the same URL. The magic of Ctrl+V. If you're not on Windows, it's not my problem. There are worse things that could happen. You could be on a ZX-81, in which case I feel for you.

Done!
Well, see how long it took you to read all of this..? The good news is, it will only take half as long for you to finish setting up the plugin.
Now, make a commit, and Push to your GitHub repo.
Watch in awe as the commit immediately shows up on your forum.
I would pee myself just thinking about it, but I haven't drunk for a while.

-- Nao.
