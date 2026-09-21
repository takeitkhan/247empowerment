<?php

function generateISODurationCode($rawInput)
{
	if (is_array($rawInput)) {
		$inputArr = $rawInput;
	} else {
		$inputArr = array_fill(0, 7, 0);

		$inputArr[3] = ($rawInput - $rawInput % 86400) / 86400;
		$inputArr[4] = (($rawInput - $rawInput % 3600) / 3600) % 24;
		$inputArr[5] = (($rawInput - $rawInput % 60) / 60) % 60;
		$inputArr[6] = $rawInput % 60;
	}

	$tIsAbsent = true;
	$output = 'P';
	$unitLetters = ['Y', 'M', 'W', 'D', 'H', 'M', 'S'];

	if (
		$inputArr[2] > 0 &&
		count(array_filter($inputArr, function ($item) {
			return $item > 0;
		})) > 1
	) {
		$inputArr[3] += $inputArr[2] * 7;
		$inputArr[2] = 0;
	}

	foreach ($inputArr as $i => $t) {
		if ($i > 3 && $tIsAbsent) {
			$output .= 'T';
			$tIsAbsent = false;
		}
		if ($t > 0) {
			$output .= round($t) . $unitLetters[$i]; //decimal values for time aren't recognized
		}
	}
	return $output;
}

function ub_convert_to_paragraphs($string)
{
	if ($string === '') {
		return '';
	} else {
		$string = explode('<br>', $string);
		$string = array_map(function ($p) {
			return '<p>' . wp_kses_post($p) . '</p>';
		}, $string);
		return implode('', $string);
	}
}


function ub_render_how_to_block($attributes, $_, $block)
{
	extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];

	$header = '';

	$timeUnits = ["second", "minute", "hour", "day", "week", "month", "year"];

	if (!in_array($firstLevelTag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong'])) {
		$firstLevelTag = 'h2';
	}
	if (!in_array($secondLevelTag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong'])) {
		$secondLevelTag = 'h3';
	}
	if (!in_array($thirdLevelTag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong'])) {
		$thirdLevelTag = 'h4';
	}

	$suppliesCode = '"supply": [';
	if ($advancedMode && $includeSuppliesList) {
		$header .= '<' . esc_attr($secondLevelTag) . '>' . wp_kses_post($suppliesIntro) . '</' . esc_attr($secondLevelTag) . '>';
		if (isset($supplies) && count($supplies) > 0) {
			$header .= $suppliesListStyle === 'ordered' ? '<ol' : '<ul';
			if ($suppliesListStyle === 'none') {
				$header .= ' style="list-style: none;"';
			}
			$header .= ' class="ub_howto-supplies-list">';
			foreach ($supplies as $i => $s) {
				$header .= '<li>' . wp_kses_post($s['name']) . ($s['imageURL'] === '' ? '' :
					'<br><img src="' . esc_url($s['imageURL']) . '"/>') . '</li>';
				if ($i > 0) {
					$suppliesCode .= ',';
				}
				$suppliesCode .= '{"@type": "HowToSupply", "name": "' . str_replace("\'", "'",  wp_kses_post($s['name'])) . '"' .
					($s['imageURL'] === '' ? '' : ',"image": "' . esc_url($s['imageURL']) . '"') . '}';
			}
			$header .= $suppliesListStyle === 'ordered' ? '</ol>' : '</ul>';
		}
	}
	$suppliesCode .= ']';

	$toolsCode = '"tool": [';

	if ($advancedMode && $includeToolsList) {
		$header .= '<' . esc_attr($secondLevelTag) . '>' . wp_kses_post($toolsIntro) . '</' . esc_attr($secondLevelTag) . '>';
		if (isset($tools) && count($tools) > 0) {
			$header .= $toolsListStyle === 'ordered' ? '<ol' : '<ul';
			if ($suppliesListStyle === 'none') {
				$header .= ' style="list-style: none;"';
			}
			$header .= ' class="ub_howto-tools-list">';
			foreach ($tools as $i => $t) {
				$header .= '<li>' . wp_kses_post($t['name']) . ($t['imageURL'] === '' ? '' :
					'<br><img src="' . esc_url($t['imageURL']) . '"/>') . '</li>';
				if ($i > 0) {
					$toolsCode .= ',';
				}
				$toolsCode .= '{"@type": "HowToTool", "name": "' . str_replace("\'", "'", wp_kses_post($t['name'])) . '"'
					. ($t['imageURL'] === '' ? '' : ',"image": "' . esc_url($t['imageURL']) . '"') . '}';
			}
			$header .= $toolsListStyle === 'ordered' ? '</ol>' : '</ul>';
		}
	}
	$toolsCode  .= ']';

	$costDisplay = $showUnitFirst ? $costCurrency . ' ' . $cost : $cost . ' ' . $costCurrency;

	$timeDisplay = '<div><' . esc_attr($secondLevelTag) . '>' . wp_kses_post($timeIntro) . '</' . esc_attr($secondLevelTag) . '>';

	$totalTimeDisplay = '';

	foreach ($totalTime as $i => $t) {
		if ($t > 0) {
			$totalTimeDisplay .= $t . ' ' . __($timeUnits[6 - $i] . ($t > 1 ? 's' : '')) . ' ';
		}
	}

	$timeDisplay .= '<p>' . wp_kses_post($totalTimeText) . wp_kses_post($totalTimeDisplay)  . '</div>';

	$ISOTotalTime = generateISODurationCode($totalTime);

	$stepsDisplay = '';
	$stepsCode = PHP_EOL .  '"step": [';

	if ($useSections) {
		$stepsDisplay = ($sectionListStyle === 'ordered' ? '<ol' : '<ul') .
			' class="ub_howto-section-display"' .
			($sectionListStyle ? ' style="list-style: none;"' : '') . '>';
		foreach ($section as $i => $s) {
			$stepsDisplay .= '<li class="ub_howto-section"><' . esc_attr($secondLevelTag) . '>' . wp_kses_post($s['sectionName']) . '</' . esc_attr($secondLevelTag) . '>' .
				($sectionListStyle === 'ordered' ? '<ol' : '<ul') . ' class="ub_howto-step-display"' . ($sectionListStyle === 'none' ? ' style="list-style: none;"' : '') . '>';
			$stepsCode .= '{"@type": "HowToSection",' . PHP_EOL
				. '"name": "' . str_replace("\'", "'", wp_kses_post($s['sectionName'])) . '",' . PHP_EOL
				. '"itemListElement": [' . PHP_EOL;
			//get each step inside section

			foreach ($s['steps'] as $j => $step) {
				$stepsCode .= '{"@type": "HowToStep",' . PHP_EOL
					. '"name": "' . str_replace("\'", "'", wp_kses_post($step['title'])) . '",' . PHP_EOL
					. ($advancedMode ? '"url": "' . get_permalink() . '#' . esc_js($step['anchor']) . '",' . PHP_EOL
						. ($step['hasVideoClip'] ? '"video":{"@id": "' . esc_js($step['anchor']) . '"},' : '') . PHP_EOL : '')
					. '"image": "' . esc_url($step['stepPic']['url']) . '",' . PHP_EOL
					. '"itemListElement" :[{' . PHP_EOL;

				$stepPic = $step['stepPic'];

				$stepsDisplay .= '<li class="ub_howto-step"' . 'style="' .
					($sectionListStyle === 'none' ? ' list-style: none;' : '') .

					(
						($stepPic['width'] > 0) ?
						'--ub-howto-image-width: ' . esc_attr($stepPic['width']) . 'px;' .
						(($stepPic['float'] === 'left') ? '--ub-howto-image-padding-right: 10px;' : '--ub-howto-image-padding-right: 0px;') .
						(($stepPic['float'] === 'right') ? '--ub-howto-image-padding-left: 10px;' : '--ub-howto-image-padding-left: 0px;') .
						(($stepPic['float'] !== 'none') ? '--ub-howto-image-float: ' . esc_attr($stepPic['float']) . ';' : '--ub-howto-image-float: none;') :
						''
					)

				. '"><' . esc_attr($thirdLevelTag) . ' id="' . esc_attr($step['anchor']) . '">'
					. wp_kses_post($step['title']) . '</' . esc_attr($thirdLevelTag) . '>' . ($step['stepPic']['url'] !== '' ?
						($step['stepPic']['caption'] === '' ? '' : '<figure>') .
						'<img class="ub_howto-step-image" src="' . esc_url($step['stepPic']['url']) . '">'
						. ($step['stepPic']['caption'] === '' ? '' : '<figcaption>' . wp_kses_post($step['stepPic']['caption']) . '</figcaption></figure>')
						: '')
					. ub_convert_to_paragraphs($step['direction']) . PHP_EOL;

				$stepsCode .= '"@type": "HowToDirection",' . PHP_EOL
					. '"text": "' . ($step['title'] === '' || !$advancedMode ? '' : str_replace("\'", "'", wp_kses_post($step['title'])) . ' ')
					. str_replace("\'", "'", wp_kses_post($step['direction'])) . '"}' . PHP_EOL;

				if ($step['tip'] !== '') {
					$stepsDisplay .= ub_convert_to_paragraphs($step['tip']);
					$stepsCode .= ',{"@type": "HowToTip",' . PHP_EOL
						. '"text": "' . str_replace("\'", "'", wp_kses_post($step['tip'])) . '"}' . PHP_EOL;
				}

				$stepsCode .= ']}' . PHP_EOL;
				$stepsDisplay .= '</li>';
				if ($j < count($s['steps']) - 1) {
					$stepsCode .= ',';
				}
			}

			$stepsDisplay .=  ($sectionListStyle === 'ordered' ? '</ol>' : '</ul>')
				. '</li>'; //close section div
			$stepsCode .= ']}';
			if ($i < count($section) - 1) {
				$stepsCode .= ',';
			}
		}
	} else {
		$stepsDisplay .=  ($sectionListStyle === 'ordered' ? '<ol' : '<ul') .
			' class="ub_howto-step-display"' . ($sectionListStyle === 'none' ? ' style="list-style: none;"' : '') . '>';
		if (isset($section) && count($section) > 0) {
			foreach ($section[0]['steps'] as $j => $step) {
				$stepPic = $step['stepPic'];
				$stepsDisplay .= '<li class="ub_howto-step" style="' . ($sectionListStyle === 'none' ? ' list-style: none;' : '') .

					(
						($stepPic['width'] > 0) ?
						'--ub-howto-image-width: ' . esc_attr($stepPic['width']) . 'px;' .
						(($stepPic['float'] === 'left') ? '--ub-howto-image-padding-right: 10px;' : '--ub-howto-image-padding-right: 0px;') .
						(($stepPic['float'] === 'right') ? '--ub-howto-image-padding-left: 10px;' : '--ub-howto-image-padding-left: 0px;') .
						(($stepPic['float'] !== 'none') ? '--ub-howto-image-float: ' . esc_attr($stepPic['float']) . ';' : '--ub-howto-image-float: none;') :
						''
					) .

				'"><' . esc_attr($thirdLevelTag) . ' id="' . esc_attr($step['anchor']) . '">'
					. wp_kses_post($step['title']) . '</' . esc_attr($thirdLevelTag) . '>' . ($step['stepPic']['url'] !== '' ?
						(!isset($step['stepPic']['caption']) && $step['stepPic']['caption'] === '' ? '' : '<figure>') .
						'<img class="ub_howto-step-image" src="' . esc_url($step['stepPic']['url']) . '">' .
						(!isset($step['stepPic']['caption']) && $step['stepPic']['caption'] === '' ? '' : '<figcaption>' . wp_kses_post($step['stepPic']['caption']) . '</figcaption></figure>') : '') .
					ub_convert_to_paragraphs($step['direction']);

				$stepsCode .= '{"@type": "HowToStep",' . PHP_EOL
					. '"name": "' . str_replace("\'", "'", wp_kses_post($step['title'])) . '",' . PHP_EOL
					. ($advancedMode ? '"url": "' . get_permalink() . '#' . esc_js($step['anchor']) . '",' . PHP_EOL
						. ($step['hasVideoClip'] ? '"video":{"@id": "' . esc_js($step['anchor']) . '"},' : '') . PHP_EOL : '')
					. '"image": "' . esc_url($step['stepPic']['url']) . '",' . PHP_EOL
					. '"itemListElement" :[{' . PHP_EOL
					. '"@type": "HowToDirection",' . PHP_EOL
					. '"text": "' . ($step['title'] === '' || !$advancedMode ? '' : str_replace("\'", "'", wp_kses_post($step['title'])) . ' ')
					. str_replace("\'", "'", wp_kses_post($step['direction'])) . '"}' . PHP_EOL;

				if ($step['tip'] !== '') {
					$stepsDisplay .= ub_convert_to_paragraphs($step['tip']);
					$stepsCode .= ',{"@type": "HowToTip",' . PHP_EOL
						. '"text": "' . str_replace("\'", "'", wp_kses_post($step['tip'])) . '"}' . PHP_EOL;
				}

				$stepsDisplay .= '</li>';
				$stepsCode .= ']}' . PHP_EOL;
				if ($j < count($section[0]['steps']) - 1) {
					$stepsCode .= ',';
				}
			}
		}
	}
	$stepsDisplay .=  $sectionListStyle === 'ordered' ? '</ol>' : '</ul>';
	$stepsCode .= ']';

	$parts = array_map(function ($sec) {
		return $sec['steps'];
	}, isset($section) ? $section : array());
	$clips = '';

	if ($videoURL !== '') {
		if (strpos($videoURL, 'https://www.youtube.com') === 0) {
			$videoClipArg = '?t=';
		}
		if (strpos($videoURL, 'https://vimeo.com') === 0) {
			$videoClipArg = '#t=';
		}
		if (strpos($videoURL, 'https://www.dailymotion.com') === 0) {
			$videoClipArg = '?start=';
		}
		if (strpos($videoURL, 'https://videopress.com') === 0) {
			$videoClipArg = '?at=';
		}
	}

	foreach ($parts as $part) {
		foreach ($part as $step) {
			if ($step['hasVideoClip']) {
				if ($clips !== '') {
					$clips .= ',';
				}
				$clips .= '{"@type": "Clip",
                            "@id": "' . esc_js($step['anchor']) . '",
                            "name": "' . str_replace("\'", "'", esc_js($step['title'])) . '",
                            "startOffset": "' . esc_js($step['videoClipStart']) . '",
                            "endOffset": "' . esc_js($step['videoClipEnd']) . '",
                            "url": "' . esc_url($videoURL) . $videoClipArg . esc_js($step['videoClipStart']) . '" }';
			}
		}
	}

	$JSONLD = '<script type="application/ld+json">
    {
        "@context": "http://schema.org",
        "@type": "HowTo",
        "name":"' . str_replace("\'", "'", wp_kses_post($title)) . '",
        "description": "' . str_replace("\'", "'", wp_kses_post($introduction)) . '",' .
		($advancedMode ?
			(array_unique($totalTime) !== array(0) ? '"totalTime": "' . $ISOTotalTime . '",' : '') .
			($videoURL === '' ? '' :
				'"video": {
                "@type": "VideoObject",
                "name": "' . str_replace("\'", "'", wp_kses_post($videoName)) . '",
                "description": "' . (str_replace("\'", "'", wp_kses_post($videoDescription)) ?:  __("No description provided")) . '",
                "duration" : "' . generateISODurationCode($videoDuration) . '",
                "thumbnailUrl": "' . esc_url($videoThumbnailURL) . '",
                "contentUrl": "' . esc_url($videoURL) . '",
                "uploadDate": "' . date('c', $videoUploadDate) . '",
                "hasPart":[' . $clips . ']
            },') .
			($cost > 0 ? '"estimatedCost": {
                "@type": "MonetaryAmount",
                "currency": "' . str_replace("\'", "'", wp_kses_post($costCurrency)) . '",
                "value": "' . wp_kses_post($cost) . '"
            },' : '')
			. $suppliesCode . ','
			. $toolsCode . ','
			: '')
		. $stepsCode . ',"yield": "' . str_replace("\'", "'", wp_kses_post($howToYield)) . '",
    "image": "' . esc_url($finalImageURL) . '"' . '}</script>';

	return '<div class="wp-block-ub-how-to ub_howto" style="' . ub_get_spacing_styles($block_attrs) . '" id="ub_howto_' . esc_attr($blockID) . '"><' . esc_attr($firstLevelTag) . '>'
		. wp_kses_post($title) . '</' . esc_attr($firstLevelTag) . '>' . ub_convert_to_paragraphs($introduction) . $header .
		($advancedMode ? ($videoURL === '' ? '' : wp_kses($videoEmbedCode, ub_get_video_embed_kses_tags()))
			. '<p>' . wp_kses_post($costDisplayText) . wp_kses_post($costDisplay) . '</p>'
			. $timeDisplay : '') . $stepsDisplay .
		'<div class="ub_howto-yield" style="' .
			(
				($finalImageWidth > 0) ?
				'--ub-howto-image-width: ' . esc_attr($finalImageWidth) . 'px;' .
				(($finalImageFloat === 'left') ? '--ub-howto-image-padding-right: 10px;' : '--ub-howto-image-padding-right: 0px;') .
				(($finalImageFloat === 'right') ? '--ub-howto-image-padding-left: 10px;' : '--ub-howto-image-padding-left: 0px;') .
				(($finalImageFloat !== 'none') ? '--ub-howto-image-float: ' . esc_attr($finalImageFloat) . ';' : '--ub-howto-image-float: none;') :
				''
			)
			. '"><' . esc_attr($secondLevelTag) . '>' . wp_kses_post($resultIntro) . '</' . esc_attr($secondLevelTag) . '>' .
		($finalImageURL === '' ? '' : (!isset($finalImageCaption) || $finalImageCaption === '' ? '' :
			'<figure class="ub_howto-yield-image-container">') .
			'<img class="ub_howto-yield-image" src="' . esc_url($finalImageURL) . '">' .
			(!isset($finalImageCaption) || $finalImageCaption === '' ? '' : '<figcaption>' . wp_kses_post($finalImageCaption) . '</figcaption></figure>')) .
		ub_convert_to_paragraphs($howToYield) . '</div>
            </div>' . $JSONLD;
}

function ub_register_how_to_block()
{
	if (function_exists('register_block_type_from_metadata')) {
		require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata(dirname(dirname(dirname(__DIR__))) . '/dist/blocks/how-to', array(
			'attributes' => $defaultValues['ub/how-to']['attributes'],
			'render_callback' => 'ub_render_how_to_block'
		));
	}
}

add_action('init', 'ub_register_how_to_block');
