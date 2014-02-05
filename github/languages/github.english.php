<?php

$txt['github'] = 'GitHub Hooks';
$txt['github_token'] = 'GitHub token';
$txt['github_token_subtext'] = '(Optional) A unique authentication token retrieved from your GitHub account. Useful only if you repost more than 60 commits per hour.';
$txt['github_secret'] = 'My secret key';
$txt['github_secret_subtext'] = 'Enter a random, secret alphanumeric key of your choosing. Then go to your GitHub repo(s), click Settings, Service Hooks, WebHook URLs, and enter: <samp>' . $GLOBALS['context']['plugins_url']['Wedge:GitHub'] . '/github.php?key=YOUR_SECRET_KEY</samp> -- you should be ready to go!';
$txt['github_admin_desc'] = 'Configure settings for GitHub webhook handling.';
$txt['github_repos'] = 'List of GitHub repositories';
$txt['github_repos_subtext'] = 'Enter one repo per line, in this form: <samp>Owner/repo:id_topic</samp>, where id_topic is the ID of the topic where new commits should be posted.';
