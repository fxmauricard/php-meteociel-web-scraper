<?php

require __DIR__ . '/vendor/autoload.php';

use Goutte\Client;
use Commando\Command;
use Fxmauricard\StringUtils;

$command = new Command();
$command->setHelp('PHP Meteociel Web Scraper');
$command->option('s')
	->aka('start_month')
	->describeAs('First month data to get.')
	->default(1);
$command->option('e')
	->aka('end_month')
	->describeAs('Last month data to get.')
	->default(12);
$command->option('y')
	->aka('year')
	->describeAs('Year of which we want data.')
	->require();
$command->option('l')
	->aka('location_id')
	->describeAs('Id of the location we want meteo data.')
	->require();

$id = $command['location_id']; // 7524
$annee = $command['year'];

for ($mois = $command['start_month'] ; $command['end_month'] <= 12 ; ++$mois) {
	$output = fopen("$id-$annee-$mois.csv", 'w');
	fputs($output, '"id","mois","jour","Heure","Visi","Temp","hum","humx","Vent","Pression","Precip"' . PHP_EOL);

	$client = new Client();

	$last_day = cal_days_in_month(CAL_GREGORIAN, $mois, $annee);
	for ($jour = 1; $jour <= $last_day; ++$jour) {
		echo "$id-$jour-$annee-$mois" . PHP_EOL;
        $url = "http://www.meteociel.fr/temps-reel/obs_villes.php?code2=$id&jour2=$jour&mois2=$mois&annee2=$annee&envoyer=OK";
        $crawler = $client->request('GET', $url);
        $filter = 'body > table > tbody > tr > td > table > tbody > tr > td > table > tbody > tr > td > center > table';
        $crawler->filter($filter)->eq(3)->filter('tr')->each(function ($node, $i) {
			global $output;
			global $id;
			global $annee;
			global $mois;
			global $jour;
			$dataLine = array();
			if ($i > 0) {
				foreach ($node->children() as $children) {
					$dataLine[] = $children->nodeValue;
				}

				$line = array(
					$id,
					$annee,
					$mois,
					$jour,
					StringUtils::cutBefore($dataLine[0], ' h'),
					StringUtils::cutBefore($dataLine[3], ' km'),
					StringUtils::cutBefore($dataLine[4], ' °C'),
					StringUtils::cutBefore($dataLine[5], '%'),
					trim(StringUtils::cutBefore($dataLine[6], '","')),
					StringUtils::cutBefore($dataLine[9], ' km/h'),
					StringUtils::cutBefore($dataLine[10], ' hPa'),
					(trim($dataLine[11]) === 'aucune') ? 0 : trim(StringUtils::cutBefore($dataLine[11], ' mm')),
				);

				fputcsv($output, $line);
			}
		});
	}

	fclose($output);
}
