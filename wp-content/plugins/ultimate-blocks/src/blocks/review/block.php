<?php


function ub_generatePercentageBar($value, $id, $activeColor, $inactiveColor ){
    $percentBar = "M 0.5,0.5 L 99.5,0.5";
    return '<div class="ub_review_percentage">
            <svg class="ub_review_percentage_bar" viewBox="0 0 100 1" preserveAspectRatio="none" height="10">
                <path
                    class="ub_review_percentage_bar_trail"
                    d="' . $percentBar . '" stroke="' . esc_attr($inactiveColor) . '"
                    stroke-width="1"
                ></path>
                <path
                    class="ub_review_percentage_bar_path"
                    d="' . $percentBar . '" stroke="' . esc_attr($activeColor) . '"
                    stroke-width="1" stroke-dashoffset="' . (100 - $value) . 'px"
                ></path>
            </svg>
            <div>' . wp_kses_post($value) . '%</div>
    </div>';
}
function ub_filterJsonldString($string){
    // Strip all HTML tags for JSON-LD compatibility
    $string = wp_strip_all_tags($string);

    // Remove line breaks and extra whitespace
    $string = preg_replace('/\s+/', ' ', $string);

    // Escape quotes and backslashes for JSON
    $string = str_replace(array('\\', '"', "\r", "\n", "\t"), array('\\\\', '\\"', '', '', ''), $string);

    // Trim whitespace
    $string = trim($string);

    return $string;
}

function ub_render_review_block($attributes, $block_content, $block_instance){
    require_once dirname(dirname(__DIR__)) . '/common.php';

    extract($attributes);
    $parsedItems = isset($parts) ? $parts : json_decode($items, true);
	$block_attrs = $block_instance->parsed_block['attrs'];

    if($blockID === ''){
        $blockID = $ID;
    }

    $extractedValues = array_map(function($item){
                                    return $item['value'];
                                }, $parsedItems);

    $average = round(array_sum($extractedValues) / count($extractedValues), 1);

    $ratings = '';

    foreach($parsedItems as $key => $item){
        $ratings .= '<div class="ub_review_' . ($valueType === 'percent' ? 'percentage_' : '') . 'entry"><span>' . wp_kses_post($item['label']) . '</span>' .
        ($valueType === 'star' ? ub_generateStarDisplay($item['value'], $starCount, $blockID . '-' . $key,
                                $inactiveStarColor, $activeStarColor, $starOutlineColor, "ub_review_stars", "ub_review_star_filter-")
                                : ub_generatePercentageBar($item['value'], $blockID . '-' . $key, $activePercentBarColor, $percentBarColor ?: '#d9d9d9')  ) . '</div>';
    }
    $button_block = !empty($block_instance->parsed_block['innerBlocks']) ? $block_instance->parsed_block['innerBlocks'][0] : array();
    $buttons = isset($button_block['attrs']['buttons']) ? $button_block['attrs']['buttons'] : array();

    $shippingDetails = null;
    if(!empty($enableShippingDetails)){
        $shippingDetails = array(
            "@type" => "OfferShippingDetails",
            "shippingRate" => array(
                "@type" => "MonetaryAmount",
                "value" => (string) $shippingRate,
                "currency" => ub_filterJsonldString($offerCurrency),
            ),
            "shippingDestination" => array(
                "@type" => "DefinedRegion",
                "addressCountry" => ub_filterJsonldString($shippingCountry),
            ),
            "deliveryTime" => array(
                "@type" => "ShippingDeliveryTime",
                "handlingTime" => array(
                    "@type" => "QuantitativeValue",
                    "minValue" => 0,
                    "maxValue" => absint($shippingHandlingDays),
                    "unitCode" => "DAY",
                ),
                "transitTime" => array(
                    "@type" => "QuantitativeValue",
                    "minValue" => 0,
                    "maxValue" => absint($shippingTransitDays),
                    "unitCode" => "DAY",
                ),
            ),
        );
    }

    $returnPolicy = null;
    if(!empty($enableReturnPolicy)){
        $returnPolicy = array(
            "@type" => "MerchantReturnPolicy",
            "applicableCountry" => ub_filterJsonldString($returnApplicableCountry),
            "returnPolicyCategory" => "https://schema.org/" . ub_filterJsonldString($returnPolicyCategory),
            "returnMethod" => "https://schema.org/" . ub_filterJsonldString($returnMethod),
            "returnFees" => "https://schema.org/" . ub_filterJsonldString($returnFees),
        );
        if($returnPolicyCategory === 'MerchantReturnFiniteReturnWindow'){
            $returnPolicy["merchantReturnDays"] = absint($merchantReturnDays);
        }
    }

    $offers = array();

    foreach ($buttons as $button) {
        $offer = array(
            "@type" => "Offer",
            "url" => esc_url($button['url']),
            "priceCurrency" => ub_filterJsonldString($offerCurrency),
            "price" => ub_filterJsonldString($offerPrice),
            "availability" => "https://schema.org/" . ub_filterJsonldString($offerStatus),
        );
        if($offerExpiry > 0){
            $offer = array_merge($offer, array('priceValidUntil'=>date("Y-m-d", $offerExpiry)));
        }
        if($shippingDetails){
            $offer['shippingDetails'] = $shippingDetails;
        }
        if($returnPolicy){
            $offer['hasMerchantReturnPolicy'] = $returnPolicy;
        }

        $offers[] = $offer;
    }

    $shippingReturnJsonFragment = '';
    if($shippingDetails){
        $shippingReturnJsonFragment .= ',"shippingDetails":' . json_encode($shippingDetails, JSON_UNESCAPED_SLASHES);
    }
    if($returnPolicy){
        $shippingReturnJsonFragment .= ',"hasMerchantReturnPolicy":' . json_encode($returnPolicy, JSON_UNESCAPED_SLASHES);
    }

    $all_buttons_offer = json_encode($offers, JSON_UNESCAPED_SLASHES);
    $aggregate_offer = '{
        "@type": "' . esc_js($offerType) . '",
        "priceCurrency": "' . ub_filterJsonldString($offerCurrency) . '",
        "lowPrice": "' . esc_js($offerLowPrice) . '",
        "highPrice": "' . esc_js($offerHighPrice) . '",
        "offerCount": "' . absint($offerCount) . '",
        "availability": "https://schema.org/' . ub_filterJsonldString($offerStatus) . '"' . $shippingReturnJsonFragment . '
    }';
    $offerCode = '"offers":' . ($offerType === 'AggregateOffer' ? $aggregate_offer : $all_buttons_offer );

    $itemExtras = '';

    $rating_value = ((int)$average % 1 === 0 ? $average : number_format($average, 1, '.', ''));
    $best_rating = ($valueType === 'star' ? $starCount : '100');
    $reviewDetails =
        ($useSummary ? '"reviewBody": "' . ub_filterJsonldString($summaryDescription) . '",' : '') .
        '"description": "' . ub_filterJsonldString($description) . '",
        "reviewRating":{
            "@type": "Rating",
            "ratingValue": "' . $rating_value . '",
            "bestRating": "' . $best_rating . '"
        },
        "author":{
            "@type": "Person",
            "name": "'. ub_filterJsonldString($authorName) .'"
        },
        "publisher": "' . ub_filterJsonldString($reviewPublisher) . '",
        "datePublished": "' . date("Y-m-d", $publicationDate) . '",
        "url": "' . get_permalink() . '"';

    switch ($itemType){
        case 'Book':
            $itemExtras = '"author": "'. ub_filterJsonldString($bookAuthorName) . '",
                            "isbn": "'. ub_filterJsonldString($isbn) . '",
                            "sameAs": "' . esc_url($itemPage) . '"';
        break;
        case 'Course':
            $itemExtras = '"provider": "' . ub_filterJsonldString($provider) . '"';
        break;
        case 'Event':
            $itemExtras = $offerCode . ',
            "startDate": "'. date("Y-m-d", $eventStartDate) . '",' .
            ($eventEndDate > 0 ? '"endDate": "'. date("Y-m-d", $eventEndDate) . '",' : '').
            '"location":{
                "@type":'. ($usePhysicalAddress ?
                            '"Place",
                "name": "' . ub_filterJsonldString($addressName) . '",
                "address": "' . ub_filterJsonldString($address) . '"' :
                            '"VirtualLocation",
                "url": "' . esc_url($eventPage) . '"').
            '},
            "organizer": "' . ub_filterJsonldString($organizer) . '",
            "performer": "' . ub_filterJsonldString($performer) . '"';
        break;
        case 'Product':
            $itemExtras = '"brand": {
                                "@type": "Brand",
                                "name": "' . ub_filterJsonldString($brand) . '"
                            },
                            "sku": "'. ub_filterJsonldString($sku) .'",
                            "' . ub_filterJsonldString($identifierType) . '": "' . ub_filterJsonldString($identifier) . '",
                            "review": {
                                "@type": "Review",
                                ' . $reviewDetails . '
                            },
                            "aggregateRating": {
                                "@type": "AggregateRating",
                                "ratingValue": "' . $rating_value . '",
                                "bestRating": "' . $best_rating . '",
                                "reviewCount": "1"
                            },' . $offerCode;
        break;
        case 'LocalBusiness':
            $itemExtras = '';
            if (isset($cuisines) && !empty($cuisines)) {
                $itemExtras .= '"servesCuisine":' . json_encode($cuisines);
                $itemExtras .= ',';
            }
            $itemExtras .= '"address": "' . ub_filterJsonldString($address) . '",
                            "telephone": "' . ub_filterJsonldString($telephone) . '",
                            "priceRange": "' . ub_filterJsonldString($priceRange) . '",
                            "sameAs": "' . esc_url($itemPage) . '"';
        break;
        case 'Movie':
            $itemExtras = '"sameAs": "' . esc_url($itemPage) . '"';
        break;
        case 'Organization':
            $itemExtras = (in_array($itemSubsubtype, array('Dentist', 'Hospital', 'MedicalClinic', 'Pharmacy', 'Physician')) ? ('"priceRange":"' . ub_filterJsonldString($priceRange) . '",'): '').
            '"address": "' . ub_filterJsonldString($address) . '",
            "telephone": "' . ub_filterJsonldString($telephone) . '"';
        break;
        case 'SoftwareApplication':
            $itemExtras = '"applicationCategory": "' . ub_filterJsonldString($appCategory) . '",
                            "operatingSystem": "' . ub_filterJsonldString($operatingSystem) . '",' . $offerCode;
        break;
        case 'MediaObject':
            $itemExtras = $itemSubtype === 'VideoObject' ?
                    ('"uploadDate": "' . date("Y-m-d", $videoUploadDate) . '",
                    "contentUrl": "' . esc_url($videoURL) . '"') : '';
        break;
        default:
            $itemExtras = '';
        break;
    }

    $reviewedItemProperties =
        '"@type":"' . esc_js($itemSubsubtype ?: $itemSubtype ?: $itemType) . '",' .
        ($itemName ? ('"name":"' . ub_filterJsonldString($itemName) . '",') : '') .
        ($imgURL ? (($itemSubtype === 'VideoObject' ? '"thumbnailUrl' : '"image') . '": "' . esc_url($imgURL) . '",') : '') .
        '"description": "' . ub_filterJsonldString($description) .'"'
        . ($itemExtras === '' ? '' : ',' . $itemExtras );
    $reviewedItem = '{' . $reviewedItemProperties . '}';

    $schema_json_content = $itemType === 'Product' ?
        '{
            "@context": "http://schema.org/",' .
            $reviewedItemProperties . '}' :
        '{
            "@context": "http://schema.org/",
            "@type": "Review",
            ' . $reviewDetails . ',
            "itemReviewed": ' . $reviewedItem . '
    }';

	// review schema filter hook
	$schema_json_content = apply_filters('ultimate-blocks/filter/review_schema', $enableReviewSchema ? $schema_json_content : '', $attributes);

	$schema_json_ld = ($enableReviewSchema ? preg_replace( '/\s+/', ' ', ('<script type="application/ld+json">' .$schema_json_content . '</script>')) : '');
	$summary_title_font_size	= isset($attributes['summaryTitleFontSize']) ? $attributes['summaryTitleFontSize'] : "24px";
	$main_title_font_size 			= isset($attributes['mainTitleFontSize']) ? $attributes['mainTitleFontSize'] : "28px";

	$summary_styles = array(
		"font-size"	=> $summary_title_font_size,
	);
	$main_title_styles = array(
		"font-size"	=> $main_title_font_size,
		"text-align" => isset($attributes['titleAlign']) ? $attributes['titleAlign'] : "",
	);
	$author_name_styles = array(
		'text-align' => isset($attributes['authorAlign']) ? $attributes['authorAlign'] : '',
	);

	$description_styles = array(
		'text-align' => isset($attributes['descriptionAlign']) ? $attributes['descriptionAlign'] : '',
	);

	$cta_main_styles = array(
		'justify-content' => isset($attributes['ctaAlignment']) ? $attributes['ctaAlignment'] : '',
	);

	$cta_main_link_styles = array(
		'color' => isset($attributes['callToActionForeColor']) ? $attributes['callToActionForeColor'] : 'inherit',
	);

	$cta_btn_styles = array(
		'color' => isset($attributes['callToActionForeColor']) ? $attributes['callToActionForeColor'] : 'inherit',
		'border-color' => isset($attributes['callToActionBorderColor']) ? $attributes['callToActionBorderColor'] : '',
		'background-color' => isset($attributes['callToActionBackColor']) ? $attributes['callToActionBackColor'] : '',
		'font-size' => isset($attributes['callToActionFontSize']) && $attributes['callToActionFontSize'] > 0 ? $attributes['callToActionFontSize'] . 'px' : '',
	);

	$image_styles = array(
		'max-height' => isset($attributes['imageSize']) ? $attributes['imageSize'] . 'px' : '',
		'max-width' => isset($attributes['imageSize']) ? $attributes['imageSize'] . 'px' : '',
	);


	$overall_value_styles = array();

	if (!$attributes['useSummary']) {
		$overall_value_styles['display'] = 'block';
	}

	$item_name = sprintf(
		'<p class="ub_review_item_name" style="%1$s">%2$s</p>',
		Ultimate_Blocks\includes\generate_css_string($main_title_styles),
		wp_kses_post($itemName)
	);

	$author_name = sprintf(
		'<p class="ub_review_author_name" style="%1$s">%2$s</p>',
		Ultimate_Blocks\includes\generate_css_string($author_name_styles),
		wp_kses_post($authorName)
	);

	$image = (!$enableImage || $imgURL === '') ? '' : sprintf(
		'<img class="ub_review_image" src="%1$s" alt="%2$s" style="%3$s">',
		esc_url($imgURL),
		esc_attr($imgAlt),
		Ultimate_Blocks\includes\generate_css_string($image_styles)
	);

	$description_html = (!$enableDescription || $description === '') ? '' : sprintf(
		'<div class="ub_review_description" style="%2$s">%1$s</div>',
		wp_kses_post($description),
		Ultimate_Blocks\includes\generate_css_string($description_styles)
	);

	$description_container = (($enableImage || $enableDescription) && ($imgURL !== '' || $description !== '')) ? sprintf(
		'<div class="ub_review_description_container ub_review_%1$s_image">%2$s%3$s</div>',
		esc_attr($imgPosition),
		$image,
		$description_html
	) : '';

	$summary_title = $useSummary ? sprintf(
		'<p class="ub_review_summary_title" style="%2$s">%1$s</p>',
		wp_kses_post($summaryTitle),
		Ultimate_Blocks\includes\generate_css_string($summary_styles)
	) : '';

	$summary_description = $useSummary ? sprintf(
		'<p>%1$s</p>',
		wp_kses_post($summaryDescription)
	) : '';

	$average_rating = sprintf(
		'<div class="ub_review_average"><span class="ub_review_rating">%1$s%2$s</span>%3$s</div>',
		$average,
		($valueType === 'percent' ? '%' : ''),
		($valueType === 'star' ? ub_generateStarDisplay($average, $starCount, $blockID . '-average', $inactiveStarColor, $activeStarColor, $starOutlineColor, "ub_review_average_stars", "ub_review_star_filter-") : '')
	);

	$cta_panel = $enableCTA ? sprintf(
		'<div class="ub_review_cta_main" style="%2$s">%1$s</div>',
		$block_content,
		Ultimate_Blocks\includes\generate_css_string($cta_main_styles)
	) : '';
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
	$styles = array(
			'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
			'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
			'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
			'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
			'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
			'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
			'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
			'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
	);

    $classes = array( 'ub_review_block', 'wp-block-ub-review' );
	if(isset($className)) {
		$classes[] = $className;
	}
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' =>  implode(' ', $classes),
			'id'    => 'ub_review_' . $blockID,
			'style' => Ultimate_Blocks\includes\generate_css_string($styles),
		)
	);

	return sprintf(
		'<div %1$s>%2$s%3$s%4$s%5$s<div class="ub_review_summary">%6$s<div class="ub_review_overall_value" style="%11$s">%7$s%8$s</div><div class="ub_review_cta_panel">%9$s</div></div>%10$s</div>',
		$wrapper_attributes,
		$item_name,
		$author_name,
		$description_container,
		$ratings,
		$summary_title,
		$summary_description,
		$average_rating,
		$cta_panel,
		$schema_json_ld,
		Ultimate_Blocks\includes\generate_css_string($overall_value_styles)
	);
}

function ub_register_review_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/review/block.json', array(
            'attributes' => $defaultValues['ub/review']['attributes'],
            'render_callback' => 'ub_render_review_block'));
    }
}

add_action('init', 'ub_register_review_block');
