<?php
/**
 * Callback for the GitHub Hooks plugin.
 *
 * Wedge (http://wedge.org)
 * Copyright © 2010 René-Gilles Deberdt, wedge.org
 * License: http://wedge.org/license/
 */

require_once('../../core/SSI.php');

global $settings;

if (!isset($_POST['payload'], $_GET['key'], $settings['github_secret']) || $_GET['key'] != $settings['github_secret'])
	die('Hacking attempt...');

$stream = stream_context_create(array('http' => array('user_agent' => 'Wedge')));
$token = isset($settings['github_token']) ? $settings['github_token'] . '@' : '';

$payload = json_decode($_POST['payload']);

if ($payload === false || !isset($payload->commits))
	exit;

$repo_url = $payload->repository->owner->name . '/' . $payload->repository->name;
$topic_ids = array();
$repolist = isset($settings['github_repos']) ? array_map('trim', preg_split('~\v~', $settings['github_repos'])) : array();
foreach ($repolist as $key => $repo)
{
	$repo = explode(':', $repo);
	if ($repo[0] == trim($repo_url))
	{
		$topic_id = trim($repo[1]);
		break;
	}
}

$request = wesql::query('
	SELECT t.id_topic, t.id_board, m.subject
	FROM {db_prefix}topics AS t
	LEFT JOIN {db_prefix}messages AS m ON (t.id_last_msg = m.id_msg)
	WHERE t.id_topic = {int:id_topic}
	LIMIT 1',
	array(
		'id_topic' => $topic_id,
	)
);
$repo = wesql::fetch_assoc($request);
wesql::free_result($request);

loadSource('Subs-Post');
$body = array();
foreach ($payload->commits as $commit)
{
	$item = file_get_contents('https://' . $token . 'api.github.com/repos/' . $repo_url . '/commits/' . $commit->id, false, $stream);
	if ($item === false)
		continue;
	$item = json_decode($item);
	$signed_off = strpos($item->commit->message, "Signed-off-by: ") !== false;

	// A lovely series of regex to turn the ugly changelog layout into Audrey Hepburn.
	// In order: Fix, Comment, Addition, Deletion, Modification.
	$log = '[list]' . str_replace(
		array('[cli=!', '[cli=@', '[cli=+', '[cli=-', '[cli=*', '[/cli]\n\n[cli'),
		array('[cli=f', '[cli=c', '[cli=a', '[cli=r', '[cli=m', '[/cli]\n[cli'),
		preg_replace(
			array(
				'~^([*+@!-]) ([^\v]+)*~m',
				'~^([^\v[].*+)$~m',
			),
			array(
				'[cli=$1]$2[/cli]',
				'[li]$1[/li]',
			),
			westr::safe(preg_replace('~\n\nSigned-off-by: [^\v]+~', '', $item->commit->message), ENT_QUOTES, false)
		)
	) . '[/list]';

	$body[] = "[Commit revision " . substr($item->sha, 0, 7) . "]\n[img align=left height=50]" . $item->author->avatar_url . "[/img][size=10pt]"
		. "[b]Author[/b]: [url=" . $item->author->html_url . "]" . $item->author->login . "[/url]" . ($signed_off ? " (Signed-off)" : "") . "\n"
		. "[b]Date[/b]: " . date('r', strtotime($item->commit->author->date)) . "\n"
		. "[b]Stats[/b]: [url=https://github.com/" . $repo_url . "/commit/" . $item->sha . "]" . count($item->files)
		. " file" . (count($item->files) > 1 ? 's' : '') . " changed[/url]; +" . $item->stats->additions
		. " (insertion" . ($item->stats->additions > 1 ? "s" : "") . "), -" . $item->stats->deletions
		. " (deletion" . ($item->stats->deletions > 1 ? "s" : "") . ")[/size]\n"
		. preg_replace('~\n\nSigned-off-by: [^\v]+~', '', $log) . "\n";
}

if (empty($body))
	exit;

$msgOptions = array(
	'subject' => $repo['subject'],
	'body' => implode($body, "[hr]\n"),
	'icon' => 'xx',
	'smileys_enabled' => 1,
);
$topicOptions = array(
	'id' => $repo['id_topic'],
	'board' => $repo['id_board'],
);
$posterOptions = array(
	'id' => 1,
	'update_post_count' => true,
);

createPost($msgOptions, $topicOptions, $posterOptions);
