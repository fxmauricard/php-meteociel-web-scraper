<?php

require __DIR__ . '/vendor/autoload.php';

use Goutte\Client;

/**
 * Class that contains useful methods for manipulating string.
 */
class StringUtils
{
	/**
	 * Method that returns a substring from the start of the haystack to the needle.
	 *
	 * @param   string  $haystack   The string in which we're searching in.
	 * @param   string  $needle     The string we are searching.
	 * @return  string              The string.
	 */
	public static function cutBefore($haystack, $needle)
	{
		$split = explode($needle, $haystack, 2);
		return array_shift($split);
	}
}

$id = 7524;
$annee = 2014;

for ($mois = 1 ; $mois <= 12 ; ++$mois) {
	$output = fopen("$id-$annee-$mois.csv", 'w');
	fputs($output, '"id","mois","jour","Heure","Visi","Temp","hum","humx","Vent","Pression","Precip"' . PHP_EOL);

	$client = new Client();

	$last_day = cal_days_in_month(CAL_GREGORIAN, $mois, $annee);
	for ($jour = 1; $jour <= $last_day; ++$jour) {
		$crawler = $client->request('GET', "http://www.meteociel.fr/temps-reel/obs_villes.php?code2=$id&jour2=$jour&mois2=$mois&annee2=$annee&envoyer=OK");
		$data = array();
		$crawler->filter('body > table > tbody > tr > td > table > tbody > tr > td > table > tbody > tr > td > center > table')->eq(3)->filter('tr')->each(function ($node, $i) {
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
