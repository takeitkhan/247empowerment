<?php
$background = esc_url($attributes['backgroundImage'] ?? '');
$youtubeID = esc_attr($attributes['youtubeID'] ?? '');
$customTitle = wp_kses_post($attributes['customTitle'] ?? '');
$headingTag = esc_html($attributes['headingTag'] ?? 'h2');
$zoomLink = esc_url($attributes['zoomLink'] ?? '');
$zoomCode = esc_html($attributes['zoomCode'] ?? '');
$zoomPassword = esc_html($attributes['zoomPassword'] ?? '');
$zoomTime = esc_html($attributes['zoomTime'] ?? '');
$appointmentLink = $attributes['appointmentLink'] ?? '';
?>
<div class="container-fluid">
	<div class="mb-4 row hero">

		<div class="bg-image p-0 rounded-3 col-12 col-md-6" style="background-image: url('<?php echo $background; ?>')">
			<div class="play-btn-wrapper">
				<div class="play-btn" data-bs-toggle="modal" data-bs-target="#videoModal">▶</div>
			</div>
		</div>
		<div class="d-flex flex-column align-items-start justify-content-center p-0 p-lg-5 col-12 col-md-6">
			<<?php echo $headingTag; ?> class="mb-4 text-white fs-giant"><?php echo $customTitle; ?></<?php echo $headingTag; ?>>
			<?php if ($zoomLink || $appointmentLink || $zoomCode || $zoomPassword) : ?>
				<div class="bg-dark shadow p-4 border rounded w-100 text-white">
					<?php if ($zoomTime): ?>
						<p class="mb-2">
							<i class="me-2 text-primary bi bi-calendar-event fs-5"></i>
							<strong>Zoom Q&A:</strong>
							<span class="bg-primary ms-1 text-light badge"><?php echo $zoomTime; ?></span>
						</p>
					<?php endif; ?>
					<?php if ($zoomCode): ?>
						<p class="mb-2">
							<i class="me-2 text-success bi bi-check2-square fs-5"></i>
							<strong>Zoom ID:</strong>
							<span class="bg-success ms-1 text-light badge"><?php echo $zoomCode; ?></span>
						</p>
					<?php endif; ?>
					<?php if ($zoomPassword): ?>
						<p class="mb-2">
							<i class="me-2 text-danger bi bi-shield-lock fs-5"></i>
							<strong>Passcode:</strong>
							<span class="bg-danger ms-1 text-light badge"><?php echo $zoomPassword; ?></span>
						</p>
					<?php endif; ?>

					<?php if ($zoomLink || $appointmentLink) : ?>
						<div class="d-flex flex-wrap align-items-center gap-3 mt-3">
							<?php if ($zoomLink): ?>
								<a href="<?php echo $zoomLink; ?>" target="_blank" class="btn btn-warning">
									<i class="bi-box-arrow-in-right me-1 bi"></i> Join Zoom Meeting
								</a>
							<?php endif; ?>

							<?php if ($appointmentLink): ?>
								<a href="<?php echo esc_url($appointmentLink); ?>" target="_blank" class="text-white fw-semibold">
									<i class="me-1 bi bi-calendar-week"></i>Book an Appointment
								</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered">
		<div class="modal-content">
			<div class="p-0 modal-body">
				<div class="xratio xratio-16x9">
					<iframe src="https://www.youtube.com/embed/<?php echo $youtubeID; ?>" title="YouTube video" allowfullscreen></iframe>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const videoModal = document.getElementById('videoModal');
		const iframe = videoModal.querySelector('iframe');
		const originalSrc = iframe.src;

		videoModal.addEventListener('hidden.bs.modal', function() {
			iframe.src = '';
			setTimeout(() => {
				iframe.src = originalSrc;
			}, 200); // small delay to reset properly
		});
	});
</script>