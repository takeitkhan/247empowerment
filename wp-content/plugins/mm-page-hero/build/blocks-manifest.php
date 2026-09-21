<?php
// This file is generated. Do not modify it manually.
return array(
	'mm-page-hero' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'mm-page-hero/mm-page-hero',
		'version' => '0.1.0',
		'title' => 'Mathmozo Page Hero Block',
		'category' => 'widgets',
		'icon' => 'smiley',
		'description' => 'Page Hero Block with Youtube Video, Background Image, Title and so on by Mathmozo IT',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'mm-page-hero',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScript' => 'file:./view.js',
		'attributes' => array(
			'backgroundImage' => array(
				'type' => 'string',
				'default' => ''
			),
			'youtubeID' => array(
				'type' => 'string',
				'default' => ''
			),
			'customTitle' => array(
				'type' => 'string',
				'default' => ''
			),
			'headingTag' => array(
				'type' => 'string',
				'default' => 'h2'
			),
			'zoomTime' => array(
				'type' => 'string',
				'default' => ''
			),
			'zoomLink' => array(
				'type' => 'string',
				'default' => ''
			),
			'zoomCode' => array(
				'type' => 'string',
				'default' => ''
			),
			'zoomPassword' => array(
				'type' => 'string',
				'default' => ''
			),
			'appointmentLink' => array(
				'type' => 'string',
				'default' => ''
			)
		)
	)
);
