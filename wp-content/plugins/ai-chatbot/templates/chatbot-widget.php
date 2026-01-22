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
			<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M0 25.9338C0 11.611 11.611 0 25.9338 0C40.2567 0 51.8677 11.611 51.8677 25.9338C51.8677 40.2567 40.2567 51.8677 25.9338 51.8677C11.611 51.8677 0 40.2567 0 25.9338Z" fill="#402A12" fill-opacity="0.2"/>
					<g clip-path="url(#clip0_1_226)">
					<path d="M22.2417 33.1375C23.9603 34.0191 25.9373 34.2579 27.8164 33.8109C29.6956 33.3638 31.3532 32.2603 32.4907 30.6992C33.6282 29.1381 34.1707 27.222 34.0205 25.2963C33.8702 23.3706 33.0372 21.5618 31.6713 20.196C30.3055 18.8302 28.4968 17.9971 26.571 17.8468C24.6453 17.6966 22.7293 18.2391 21.1681 19.3766C19.607 20.5141 18.5035 22.1717 18.0564 24.0509C17.6094 25.93 17.8482 27.907 18.7298 29.6256L16.9288 34.9385L22.2417 33.1375Z" stroke="#402A12" stroke-width="1.80096" stroke-linecap="round" stroke-linejoin="round"/>
					</g>
					<defs>
						<clipPath id="clip0_1_226">
						<rect width="21.6115" height="21.6115" fill="white" transform="translate(15.1281 15.1279)"/>
						</clipPath>
					</defs>
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
				<div class="ai-chatbot-send-icon-wrapper">
				<button class="ai-chatbot-send-icon" id="ai-chatbot-send-icon" aria-label="<?php esc_attr_e( 'Send message', 'ai-chatbot' ); ?>">
					<!-- <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M20 2L9 13M20 2L13 20L9 13M20 2L0 8L9 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg> -->
				</button>
				<svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M0 25.9338C0 11.611 11.611 0 25.9338 0C40.2567 0 51.8677 11.611 51.8677 25.9338C51.8677 40.2567 40.2567 51.8677 25.9338 51.8677C11.611 51.8677 0 40.2567 0 25.9338Z" fill="#402A12" fill-opacity="0.2"/>
					<g clip-path="url(#clip0_1_226)">
					<path d="M22.2417 33.1375C23.9603 34.0191 25.9373 34.2579 27.8164 33.8109C29.6956 33.3638 31.3532 32.2603 32.4907 30.6992C33.6282 29.1381 34.1707 27.222 34.0205 25.2963C33.8702 23.3706 33.0372 21.5618 31.6713 20.196C30.3055 18.8302 28.4968 17.9971 26.571 17.8468C24.6453 17.6966 22.7293 18.2391 21.1681 19.3766C19.607 20.5141 18.5035 22.1717 18.0564 24.0509C17.6094 25.93 17.8482 27.907 18.7298 29.6256L16.9288 34.9385L22.2417 33.1375Z" stroke="#402A12" stroke-width="1.80096" stroke-linecap="round" stroke-linejoin="round"/>
					</g>
					<defs>
						<clipPath id="clip0_1_226">
						<rect width="21.6115" height="21.6115" fill="white" transform="translate(15.1281 15.1279)"/>
						</clipPath>
					</defs>
				</svg>
				</div>
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
