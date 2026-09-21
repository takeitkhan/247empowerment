<?php
/**
 * KB Manager - Knowledge Base Editor
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_KB_Manager {

	/**
	 * Initialize KB manager
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'mm-ai-chat' ) === false ) {
			return;
		}
		wp_enqueue_style( 'mm-ai-chat-admin', MM_AI_CHAT_PLUGIN_URL . 'admin/css/admin-styles.css', array(), MM_AI_CHAT_VERSION );
	}

	/**
	 * Render KB manager page
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_ai_chat' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Ensure classes are loaded
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-knowledge-base.php';

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		if ( 'add' === $action ) {
			// Add new item form
			self::render_edit_form( null );
		} elseif ( 'edit' === $action && isset( $_GET['kb_id'] ) ) {
			// Edit existing item
			self::render_edit_form( sanitize_text_field( $_GET['kb_id'] ) );
		} else {
			// List items
			self::render_list();
		}
	}

	/**
	 * Render KB items list
	 */
	private static function render_list() {
		$search   = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
		$category = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : '';
		$items    = MM_AI_Chat_Knowledge_Base::get_items( array(
			'search'   => $search,
			'category' => $category,
			'limit'    => 50,
		) );
		$categories = MM_AI_Chat_Knowledge_Base::get_categories();
		?>

		<div class="wrap">
			<h1>
				<?php esc_html_e( 'Knowledge Base Manager', 'mm-ai-chat' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mm-ai-chat-kb&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( '+ Add New Item', 'mm-ai-chat' ); ?></a>
			</h1>

			<!-- Search and Filter Form -->
			<form method="get" class="">
				<input type="hidden" name="page" value="mm-ai-chat-kb" />
				<div class="top tablenav">
					<div class="alignleft actions">
						<input type="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search questions...', 'mm-ai-chat' ); ?>" class="regular-text" />
						<select name="category">
							<option value=""><?php esc_html_e( 'All Categories', 'mm-ai-chat' ); ?></option>
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $category, $cat ); ?>><?php echo esc_html( $cat ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php submit_button( __( 'Filter', 'mm-ai-chat' ), '', 'filter', false ); ?>
					</div>
				</div>
			</form>

			<!-- Items Table -->
			<table class="wp-list-table fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Question', 'mm-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Category', 'mm-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'mm-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Status', 'mm-ai-chat' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'mm-ai-chat' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $items ) ) : ?>
						<?php foreach ( $items as $item ) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html( wp_trim_words( $item->question, 12, '...' ) ); ?></strong>
									<br />
									<small style="color: #999;"><?php echo esc_html( wp_trim_words( $item->answer, 20, '...' ) ); ?></small>
								</td>
								<td><?php echo esc_html( $item->category ); ?></td>
								<td><?php echo esc_html( $item->priority ); ?></td>
								<td>
									<?php if ( $item->is_active ) : ?>
										<mark class="unapproved"><span aria-hidden="true">✓</span> <?php esc_html_e( 'Active', 'mm-ai-chat' ); ?></mark>
									<?php else : ?>
										<mark><span aria-hidden="true">✗</span> <?php esc_html_e( 'Inactive', 'mm-ai-chat' ); ?></mark>
									<?php endif; ?>
								</td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=mm-ai-chat-kb&action=edit&kb_id=' . urlencode( $item->kb_id ) ) ); ?>"><?php esc_html_e( 'Edit', 'mm-ai-chat' ); ?></a> |
									<a href="#" onclick="deleteKBItem('<?php echo esc_attr( $item->kb_id ); ?>', event)" class="submitdelete"><?php esc_html_e( 'Delete', 'mm-ai-chat' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5">
								<em><?php esc_html_e( 'No knowledge base items found.', 'mm-ai-chat' ); ?></em>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<script>
			function deleteKBItem(kbId, event) {
				event.preventDefault();
				if (confirm('<?php esc_attr_e( 'Are you sure you want to delete this item? This action cannot be undone.', 'mm-ai-chat' ); ?>')) {
					fetch('<?php echo esc_url( rest_url( 'mm-ai-chat/v1/admin/knowledge-base' ) ); ?>' + '/' + kbId, {
						method: 'DELETE',
						headers: {
							'X-WP-Nonce': '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>'
						}
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							location.reload();
						} else {
							alert('<?php esc_attr_e( 'Error: ', 'mm-ai-chat' ); ?>' + (data.message || '<?php esc_attr_e( 'Unknown error', 'mm-ai-chat' ); ?>'));
						}
					});
				}
			}
		</script>
		<?php
	}

	/**
	 * Render edit/add form
	 */
	private static function render_edit_form( $kb_id = null ) {
		$item = null;
		if ( $kb_id ) {
			global $wpdb;
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ai_knowledge_base WHERE kb_id = %s", $kb_id ) );
		}

		$categories = MM_AI_Chat_Knowledge_Base::get_categories();
		?>

		<div class="wrap">
			<h1>
				<?php echo $item ? esc_html__( 'Edit Knowledge Base Item', 'mm-ai-chat' ) : esc_html__( 'Add New Knowledge Base Item', 'mm-ai-chat' ); ?>
			</h1>

			<form id="kb-form" method="post" style="max-width: 800px; margin-bottom: 40px;">
				<table class="form-table">
									<tr class="form-field">
										<th scope="row">
											<label for="category"><?php esc_html_e( 'Category', 'mm-ai-chat' ); ?> <span class="description">(<?php esc_html_e( 'required', 'mm-ai-chat' ); ?>)</span></label>
										</th>
										<td>
											<select id="category" name="category" required>
												<option value=""><?php esc_html_e( 'Select a category...', 'mm-ai-chat' ); ?></option>
												<?php foreach ( $categories as $cat ) : ?>
													<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $item->category ?? '', $cat ); ?>><?php echo esc_html( $cat ); ?></option>
												<?php endforeach; ?>
												<option value="__new__">+ <?php esc_html_e( 'Create New Category', 'mm-ai-chat' ); ?></option>
											</select>
										</td>
									</tr>
									<tr class="form-field" id="new_category_row" style="display: none;">
										<th scope="row">
											<label for="new_category"><?php esc_html_e( 'New Category Name', 'mm-ai-chat' ); ?></label>
										</th>
										<td>
											<input type="text" id="new_category" name="new_category" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., Billing, Technical Support', 'mm-ai-chat' ); ?>" />
										</td>
									</tr>
									<tr class="form-field">
										<th scope="row">
											<label for="question"><?php esc_html_e( 'Question/Title', 'mm-ai-chat' ); ?> <span class="description">(<?php esc_html_e( 'required', 'mm-ai-chat' ); ?>)</span></label>
										</th>
										<td>
											<input type="text" id="question" name="question" class="large-text" value="<?php echo esc_attr( $item->question ?? '' ); ?>" placeholder="<?php esc_attr_e( 'What is this question about?', 'mm-ai-chat' ); ?>" required />
											<p class="description"><?php esc_html_e( 'The question that users might ask', 'mm-ai-chat' ); ?></p>
										</td>
									</tr>
									<tr class="form-field">
										<th scope="row">
											<label for="answer"><?php esc_html_e( 'Answer', 'mm-ai-chat' ); ?> <span class="description">(<?php esc_html_e( 'required', 'mm-ai-chat' ); ?>)</span></label>
										</th>
										<td>
											<?php
											wp_editor( $item->answer ?? '', 'answer', array(
												'textarea_rows' => 12,
												'media_buttons' => false,
												'teeny'         => false,
												'quicktags'     => true,
											) );
											?>
											<p class="description"><?php esc_html_e( 'The AI response for this question. You can use rich formatting here.', 'mm-ai-chat' ); ?></p>
										</td>
									</tr>
									<tr class="form-field">
										<th scope="row">
											<label for="keywords"><?php esc_html_e( 'Keywords', 'mm-ai-chat' ); ?></label>
										</th>
										<td>
											<input type="text" id="keywords" name="keywords" class="large-text" value="<?php echo esc_attr( $item->keywords ?? '' ); ?>" placeholder="<?php esc_attr_e( 'e.g., payment, invoice, billing, charge', 'mm-ai-chat' ); ?>" />
											<p class="description"><?php esc_html_e( 'Comma-separated keywords for matching user queries. Leave empty for exact match only.', 'mm-ai-chat' ); ?></p>
										</td>
									</tr>
									<tr class="form-field">
										<th scope="row">
											<label for="priority"><?php esc_html_e( 'Priority', 'mm-ai-chat' ); ?></label>
										</th>
										<td>
											<input type="number" id="priority" name="priority" min="1" max="100" value="<?php echo esc_attr( $item->priority ?? 50 ); ?>" style="max-width: 80px;" />
											<p class="description"><?php esc_html_e( 'Priority for ranking results. 1 = highest, 100 = lowest', 'mm-ai-chat' ); ?></p>
										</td>
									</tr>
									<tr class="form-field">
										<th scope="row">
											<label for="is_active"><?php esc_html_e( 'Status', 'mm-ai-chat' ); ?></label>
										</th>
										<td>
											<label>
												<input type="checkbox" id="is_active" name="is_active" value="1" <?php checked( $item->is_active ?? 1, 1 ); ?> />
												<?php esc_html_e( 'Active (show in AI responses)', 'mm-ai-chat' ); ?>
											</label>
											<p class="description"><?php esc_html_e( 'Uncheck to temporarily disable this item without deleting it', 'mm-ai-chat' ); ?></p>
										</td>
									</tr>
								</table>

				<!-- Submit & Cancel -->
				<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
					<button type="button" class="button button-primary button-large" onclick="saveKBItem(<?php echo $item ? 'true' : 'false'; ?>, '<?php echo esc_attr( $item->kb_id ?? '' ); ?>', event)" style="padding: 8px 20px; font-size: 14px;">
						<?php echo $item ? esc_html__( '✏️ Update Item', 'mm-ai-chat' ) : esc_html__( '✨ Publish Item', 'mm-ai-chat' ); ?>
					</button>
					
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mm-ai-chat-kb' ) ); ?>" class="button" style="margin-left: 10px; padding: 8px 20px; font-size: 14px;">
						<?php esc_html_e( '← Cancel', 'mm-ai-chat' ); ?>
					</a>

					<?php if ( $item ) : ?>
						<button type="button" class="button button-link button-link-delete" onclick="deleteKBItem('<?php echo esc_attr( $item->kb_id ); ?>', event)" style="margin-left: 10px; color: #a00; text-decoration: none;">
							<?php esc_html_e( '🗑️ Delete Item', 'mm-ai-chat' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</form>

			<p style="margin-top: 40px; color: #666; font-size: 13px;">
				<?php esc_html_e( '💡 Tip: Use keywords to help the AI find this answer when users ask related questions.', 'mm-ai-chat' ); ?>
			</p>
		</div>

		<script>
			document.getElementById('category').addEventListener('change', function() {
				document.getElementById('new_category_row').style.display = this.value === '__new__' ? 'table-row' : 'none';
			});

			function saveKBItem(isEdit, kbId, event) {
				event.preventDefault();
				
				const formData = new FormData(document.getElementById('kb-form'));
				
				// Get TinyMCE content with fallback
				let answerContent = '';
				if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
					answerContent = tinymce.get('answer').getContent();
				} else {
					answerContent = document.getElementById('answer').value;
				}

				const categoryValue = formData.get('category');
				const categoryToSend = categoryValue === '__new__' ? formData.get('new_category') : categoryValue;

				const data = {
					category: categoryToSend,
					question: formData.get('question'),
					answer: answerContent,
					keywords: formData.get('keywords') || '',
					priority: parseInt(formData.get('priority')) || 50,
					is_active: formData.get('is_active') ? 1 : 0
				};

				// Validation
				if (!data.category || data.category.trim() === '') {
					alert('<?php esc_attr_e( 'Please select or create a category', 'mm-ai-chat' ); ?>');
					return;
				}
				if (!data.question || data.question.trim() === '') {
					alert('<?php esc_attr_e( 'Please enter a question', 'mm-ai-chat' ); ?>');
					return;
				}
				if (!data.answer || data.answer.trim() === '') {
					alert('<?php esc_attr_e( 'Please enter an answer', 'mm-ai-chat' ); ?>');
					return;
				}

				const url = isEdit 
					? '<?php echo esc_url( rest_url( 'mm-ai-chat/v1/admin/knowledge-base' ) ); ?>/' + kbId
					: '<?php echo esc_url( rest_url( 'mm-ai-chat/v1/admin/knowledge-base' ) ); ?>';

				const method = isEdit ? 'PUT' : 'POST';

				console.log('Sending request:', { method, url, data });

				fetch(url, {
					method: method,
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					body: JSON.stringify(data)
				})
				.then(response => {
					console.log('Response status:', response.status);
					return response.json().then(data => ({ status: response.status, body: data }));
				})
				.then(({ status, body }) => {
					console.log('Response body:', body);
					if (status >= 200 && status < 300) {
						window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=mm-ai-chat-kb' ) ); ?>';
					} else {
						alert('✗ Error: ' + (body.error || body.message || 'Unknown error'));
					}
				})
				.catch(error => {
					console.error('Request error:', error);
					alert('✗ Request failed: ' + error.message);
				});
			}

			function deleteKBItem(kbId, event) {
				event.preventDefault();
				if (confirm('<?php esc_attr_e( 'Are you sure you want to delete this item? This action cannot be undone.', 'mm-ai-chat' ); ?>')) {
					fetch('<?php echo esc_url( rest_url( 'mm-ai-chat/v1/admin/knowledge-base' ) ); ?>' + '/' + kbId, {
						method: 'DELETE',
						headers: {
							'X-WP-Nonce': '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>'
						}
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							location.reload();
						} else {
							alert('<?php esc_attr_e( 'Error: ', 'mm-ai-chat' ); ?>' + (data.message || '<?php esc_attr_e( 'Unknown error', 'mm-ai-chat' ); ?>'));
						}
					});
				}
			}
		</script>
		<?php
	}
}
