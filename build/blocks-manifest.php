<?php
// This file is generated. Do not modify it manually.
return array(
	'build' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'dcsb/dynamic-content',
		'version' => '0.1.1',
		'title' => 'Dynamic Content Showcase',
		'category' => 'widgets',
		'icon' => 'excerpt-view',
		'description' => 'Display posts, pages, or custom content dynamically with filtering options.',
		'keywords' => array(
			'posts',
			'dynamic',
			'content',
			'showcase',
			'query',
			'filter'
		),
		'attributes' => array(
			'postType' => array(
				'type' => 'string',
				'default' => 'post'
			),
			'numberOfPosts' => array(
				'type' => 'number',
				'default' => 3
			),
			'orderBy' => array(
				'type' => 'string',
				'default' => 'date'
			),
			'order' => array(
				'type' => 'string',
				'default' => 'DESC'
			),
			'selectedTaxonomy' => array(
				'type' => 'string',
				'default' => ''
			),
			'selectedTerms' => array(
				'type' => 'array',
				'default' => array(
					
				),
				'items' => array(
					'type' => 'number'
				)
			),
			'showExcerpt' => array(
				'type' => 'boolean',
				'default' => true
			),
			'showThumbnail' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'supports' => array(
			'html' => false,
			'align' => array(
				'wide',
				'full'
			),
			'spacing' => array(
				'margin' => true,
				'padding' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true
			)
		),
		'textdomain' => 'dcsb',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style.css'
	)
);
