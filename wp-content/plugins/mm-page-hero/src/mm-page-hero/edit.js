import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	MediaUpload,
	MediaUploadCheck,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	Button,
	SelectControl
} from '@wordpress/components';

import './editor.scss';

export default function Edit({ attributes, setAttributes }) {
	const {
		backgroundImage,
		youtubeID,
		customTitle,
		headingTag,
		zoomTime,
		zoomLink,		
		zoomCode,
		zoomPassword,
		appointmentLink,
	} = attributes;

	const onSelectImage = (media) => {
		setAttributes({ backgroundImage: media.url });
	};

	const tagOptions = [
		{ label: 'H1', value: 'h1' },
		{ label: 'H2', value: 'h2' },
		{ label: 'H3', value: 'h3' },
		{ label: 'H4', value: 'h4' },
		{ label: 'H5', value: 'h5' },
		{ label: 'H6', value: 'h6' },
	];


	return (
		<section {...useBlockProps()}>
			<InspectorControls>
				<PanelBody title={__('Left Column Settings', 'mm-page-hero')} initialOpen={true}>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={onSelectImage}
							allowedTypes={['image']}
							render={({ open }) => (
								<Button onClick={open} variant="secondary">
									{backgroundImage
										? __('Change Background Image', 'mm-page-hero')
										: __('Set Background Image', 'mm-page-hero')}
								</Button>
							)}
						/>
					</MediaUploadCheck>
					<TextControl
						label={__('YouTube Video ID', 'mm-page-hero')}
						value={youtubeID}
						onChange={(val) => setAttributes({ youtubeID: val })}
					/>
				</PanelBody>

				<PanelBody title={__('Right Column Settings', 'mm-page-hero')} initialOpen={false}>
					<SelectControl
						label={__('Heading Tag', 'mm-page-hero')}
						value={headingTag}
						options={tagOptions}
						onChange={(val) => setAttributes({ headingTag: val })}
					/>
					<TextControl
						label={__('Zoom Meeting Schedule', 'mm-page-hero')}
						value={zoomTime}
						onChange={(val) => setAttributes({ zoomTime: val })}
					/>
					<TextControl
						label={__('Zoom Meeting Link', 'mm-page-hero')}
						value={zoomLink}
						onChange={(val) => setAttributes({ zoomLink: val })}
					/>
					<TextControl
						label={__('Meeting Code', 'mm-page-hero')}
						value={zoomCode}
						onChange={(val) => setAttributes({ zoomCode: val })}
					/>
					<TextControl
						label={__('Meeting Password', 'mm-page-hero')}
						value={zoomPassword}
						onChange={(val) => setAttributes({ zoomPassword: val })}
					/>
					<TextControl
						label={__('Appointment Link', 'mm-page-hero')}
						value={appointmentLink}
						onChange={(val) => setAttributes({ appointmentLink: val })}
					/>
				</PanelBody>
			</InspectorControls>
			<div className='two-column-hero'>
				<div
					className="mmblock-left"
					style={{
						backgroundImage: `url(${backgroundImage})`,
					}}
				>
					{youtubeID && (
						<div className="mmblock-play-button">
							<span className="pulse"></span>
							<Button
								className="play-btn"
								onClick={() => alert('Modal logic will go here')}
								label={__('Play Video', 'mm-page-hero')}
							>
								<i className="bi bi-play-fill"></i>
							</Button>
						</div>
					)}
				</div>
				<div className="mmblock-right">
					<RichText
						tagName={headingTag}
						value={customTitle}
						onChange={(val) => setAttributes({ customTitle: val })}
						allowedFormats={['core/bold', 'core/italic', 'core/underline']}
						placeholder={__('Write a custom title…', 'mm-page-hero')}
					/>

					<div className="zoom-info">
						{zoomTime && (
								<p>
									<strong>{__('Zoom Schedule:', 'mm-page-hero')}</strong>{' '}
									<a href={zoomTime} target="_blank" rel="noopener noreferrer">
										{zoomTime}
									</a>
								</p>
							)
						}
						{zoomLink && (
							<p>
								<strong>{__('Zoom Link:', 'mm-page-hero')}</strong>{' '}
								<a href={zoomLink} target="_blank" rel="noopener noreferrer">
									{zoomLink}
								</a>
							</p>
						)}
						{appointmentLink && (
							<p>
								<strong>{__('Appointment Link:', 'mm-page-hero')}</strong>{' '}
								<a href={appointmentLink} target="_blank" rel="noopener noreferrer">
									{appointmentLink}
								</a>
							</p>
						)}						
						{zoomCode && (
							<p>
								<strong>{__('Meeting Code:', 'mm-page-hero')}</strong> {zoomCode}
							</p>
						)}
						{zoomPassword && (
							<p>
								<strong>{__('Password:', 'mm-page-hero')}</strong> {zoomPassword}
							</p>
						)}
					</div>
				</div>
			</div>
		</section>
	);
}
