<?php
/**
 * Chatbot Widget Template
 * 
 * @package AI_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$assistant_name = get_option( 'ai_chatbot_assistant_name', 'Visage Assistant' );
$welcome_message = get_option( 'ai_chatbot_welcome_message', 'Hi! How can I help you choose the right program?' );
?>

<div id="ai-chatbot-widget" class="ai-chatbot-widget">
	<div class="ai-chatbot-container" id="ai-chatbot-container">
		<div class="ai-chatbot-header">
			<div class="ai-chatbot-header-icon">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="currentColor"/>
					<path d="M7 9H17V11H7V9ZM7 12H15V14H7V12Z" fill="currentColor"/>
				</svg>
			</div>
			<div class="ai-chatbot-header-content">
				<h3 class="ai-chatbot-assistant-name"><?php echo esc_html( $assistant_name ); ?></h3>
				<div class="ai-chatbot-status">
					<span class="ai-chatbot-status-dot"></span>
					<span class="ai-chatbot-status-text"><?php esc_html_e( 'Online now', 'ai-chatbot' ); ?></span>
				</div>
			</div>
		</div>
		
		<div class="ai-chatbot-messages" id="ai-chatbot-messages">
			<div class="ai-chatbot-message ai-chatbot-message-bot">
				<div class="ai-chatbot-message-content">
					<?php echo esc_html( $welcome_message ); ?>
				</div>
			</div>
		</div>
		
		<div class="ai-chatbot-options" id="ai-chatbot-options">
			<!-- Options will be dynamically inserted here -->
		</div>
		
		<div class="ai-chatbot-input-area">
			<div class="ai-chatbot-input-wrapper">
				<input 
					type="text" 
					id="ai-chatbot-input" 
					class="ai-chatbot-input" 
					placeholder="<?php esc_attr_e( 'Type your message...', 'ai-chatbot' ); ?>"
					autocomplete="off"
				/>
				<button class="ai-chatbot-send-icon" id="ai-chatbot-send-icon" aria-label="<?php esc_attr_e( 'Send message', 'ai-chatbot' ); ?>">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M20 2L9 13M20 2L13 20L9 13M20 2L0 8L9 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
			<button class="ai-chatbot-chat-now" id="ai-chatbot-chat-now">
				<?php esc_html_e( 'Chat Now', 'ai-chatbot' ); ?>
			</button>
		</div>
		
		<div class="ai-chatbot-typing" id="ai-chatbot-typing" style="display: none;">
			<div class="ai-chatbot-typing-indicator">
				<span></span>
				<span></span>
				<span></span>
			</div>
		</div>
	</div>
</div>
