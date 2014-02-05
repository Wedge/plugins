<?php

$txt['github'] = 'GitHub Hooks';
$txt['github_token'] = 'Clé d\'authentification GitHub';
$txt['github_token_subtext'] = '(Facultative) Une clé (authentication token) fournie par GitHub sur votre compte. Pratique, mais inutile si vous importez moins de 60 commits par heure.';
$txt['github_secret'] = 'Ma clé secrète';
$txt['github_secret_subtext'] = 'Entrez une clé de votre choix, alphanumérique et longue de préférence. Puis allez sur vos dépôts GitHub, cliquez sur Settings, puis Service Hooks, puis WebHook URLs, et entrez&#8239;: <samp>' . $GLOBALS['context']['plugins_url']['Wedge:GitHub'] . '/github.php?key=MA_CLE_SECRETE</samp> -- et c\'est parti&#8239;!';
$txt['github_admin_desc'] = 'Configurez ici les paramètres de rapatriement de vos commits sur GitHub.';
$txt['github_repos'] = 'Liste de dépôts GitHub';
$txt['github_repos_subtext'] = 'Entrez un dépôt (repo) par ligne, sous la forme <samp>Auteur/depot:id_sujet</samp>, où id_sujet est le numéro du sujet où les nouveaux commits doivent être repostés.';
