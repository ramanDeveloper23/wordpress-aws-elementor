/**
 * AI Chatbot JavaScript
 */

(function($) {
	'use strict';
	
	const Chatbot = {
		init: function() {
			this.bindEvents();
			this.loadInitialOptions();
		},
		
		bindEvents: function() {
			// Handle option clicks
			$(document).on('click', '.ai-chatbot-option', function() {
				const questionId = $(this).data('question-id');
				const optionType = $(this).data('option-type');
				const optionUrl = $(this).data('option-url');
				
				if (optionType === 'link' && optionUrl) {
					// Handle link option
					Chatbot.handleLinkOption(questionId, optionUrl);
				} else {
					// Handle regular option
					Chatbot.handleOptionClick(questionId);
				}
			});
			
			// Handle text input Enter key
			$('#ai-chatbot-input').on('keypress', function(e) {
				if (e.which === 13) {
					e.preventDefault();
					Chatbot.handleTextInput();
				}
			});
			
			// Handle send icon click
			$('#ai-chatbot-send-icon').on('click', function() {
				Chatbot.handleTextInput();
			});
			
			// Handle Chat Now button
			$('#ai-chatbot-chat-now').on('click', function() {
				Chatbot.handleTextInput();
			});
		},
		
		handleTextInput: function() {
			const input = $('#ai-chatbot-input');
			const message = input.val().trim();
			
			if (message === '') {
				return;
			}
			
			// Add user message
			this.addMessage(message, 'user');
			
			// Clear input
			input.val('');
			
			// Try to match message to predefined options
			const matchedOption = this.matchMessageToOption(message);
			
			if (matchedOption) {
				const questionId = matchedOption.id;
				const optionType = matchedOption.type;
				const optionUrl = matchedOption.url;
				
				if (optionType === 'link' && optionUrl) {
					this.handleLinkOption(questionId, optionUrl);
				} else {
					this.handleOptionClick(questionId);
				}
			} else {
				// Show typing and provide generic response
				this.showTyping();
				setTimeout(() => {
					this.hideTyping();
					this.addMessage('I understand you\'re interested in ' + message + '. Let me help you find the right option. Please select from the options below or ask about our bridal makeup or learn makeup services.', 'bot');
					// Reload initial options
					if (aiChatbot.initialOptions && aiChatbot.initialOptions.length > 0) {
						this.displayOptions(aiChatbot.initialOptions);
					} else {
						this.handleOptionClick('greeting', false);
					}
				}, 1000);
			}
		},
		
		matchMessageToOption: function(message) {
			const lowerMessage = message.toLowerCase();
			
			// Match common phrases to options
			if (lowerMessage.includes('bridal') || lowerMessage.includes('wedding') || lowerMessage.includes('bride')) {
				return { id: 'bridal_makeup', type: null, url: null };
			}
			if (lowerMessage.includes('learn') || lowerMessage.includes('class') || lowerMessage.includes('course') || lowerMessage.includes('training')) {
				return { id: 'learn_makeup', type: null, url: null };
			}
			if (lowerMessage.includes('price') || lowerMessage.includes('cost') || lowerMessage.includes('pricing')) {
				return { id: 'pricing', type: null, url: null };
			}
			if (lowerMessage.includes('contact') || lowerMessage.includes('book') || lowerMessage.includes('appointment')) {
				return { id: 'contact', type: null, url: null };
			}
			
			return null;
		},
		
		loadInitialOptions: function() {
			// Load initial greeting options from localized data
			if (aiChatbot.initialOptions && aiChatbot.initialOptions.length > 0) {
				this.displayOptions(aiChatbot.initialOptions);
			} else {
				// Fallback: load via AJAX
				this.handleOptionClick('greeting', false);
			}
		},
		
		handleOptionClick: function(questionId, showUserMessage = true) {
			// Show typing indicator
			this.showTyping();
			
			// Add user message if needed
			if (showUserMessage) {
				const optionText = $('.ai-chatbot-option[data-question-id="' + questionId + '"]').text();
				this.addMessage(optionText, 'user');
			}
			
			// Get response via AJAX
			$.ajax({
				url: aiChatbot.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ai_chatbot_response',
					question_id: questionId,
					nonce: aiChatbot.nonce
				},
				success: (response) => {
					this.hideTyping();
					
					if (response.success && response.data) {
						const data = response.data;
						
						// Add bot message
						if (data.message) {
							this.addMessage(data.message, 'bot');
						}
						
						// Handle redirect
						if (data.redirect) {
							setTimeout(() => {
								window.location.href = data.redirect;
							}, 1000);
							return;
						}
						
						// Display options
						if (data.options && data.options.length > 0) {
							this.displayOptions(data.options);
						} else {
							this.clearOptions();
						}
					} else {
						this.addMessage('Sorry, I encountered an error. Please try again.', 'bot');
					}
				},
				error: () => {
					this.hideTyping();
					this.addMessage('Sorry, I encountered an error. Please try again.', 'bot');
				}
			});
		},
		
		handleLinkOption: function(questionId, url) {
			// Show typing indicator
			this.showTyping();
			
			// Add user message
			const optionText = $('.ai-chatbot-option[data-question-id="' + questionId + '"]').text();
			this.addMessage(optionText, 'user');
			
			// Use provided URL or get from serviceUrls
			let redirectUrl = url;
			if (!redirectUrl) {
				if (questionId === 'view_bridal') {
					redirectUrl = aiChatbot.serviceUrls.bridal_makeup;
				} else if (questionId === 'view_learn') {
					redirectUrl = aiChatbot.serviceUrls.learn_makeup;
				}
			}
			
			// Get response
			$.ajax({
				url: aiChatbot.ajaxUrl,
				type: 'POST',
				data: {
					action: 'ai_chatbot_response',
					question_id: questionId,
					nonce: aiChatbot.nonce
				},
				success: (response) => {
					this.hideTyping();
					
					if (response.success && response.data) {
						const data = response.data;
						
						// Add bot message
						if (data.message) {
							this.addMessage(data.message, 'bot');
						}
						
						// Use redirect URL from response or fallback to provided URL
						const finalUrl = data.redirect || redirectUrl;
						
						// Redirect after short delay
						if (finalUrl) {
							setTimeout(() => {
								window.location.href = finalUrl;
							}, 1000);
						}
					} else if (redirectUrl) {
						// Fallback redirect if AJAX fails
						setTimeout(() => {
							window.location.href = redirectUrl;
						}, 500);
					}
				},
				error: () => {
					this.hideTyping();
					this.addMessage('Redirecting...', 'bot');
					if (redirectUrl) {
						setTimeout(() => {
							window.location.href = redirectUrl;
						}, 500);
					}
				}
			});
		},
		
		addMessage: function(text, type) {
			const messageHtml = `
				<div class="ai-chatbot-message ai-chatbot-message-${type}">
					<div class="ai-chatbot-message-content">
						${this.escapeHtml(text)}
					</div>
				</div>
			`;
			
			$('#ai-chatbot-messages').append(messageHtml);
			this.scrollToBottom();
		},
		
		displayOptions: function(options) {
			let optionsHtml = '';
			
			options.forEach((option) => {
				const optionClass = option.type === 'link' ? 'ai-chatbot-option-link' : '';
				const dataAttrs = `data-question-id="${this.escapeHtml(option.id)}"`;
				const typeAttr = option.type ? `data-option-type="${this.escapeHtml(option.type)}"` : '';
				const urlAttr = option.url ? `data-option-url="${this.escapeHtml(option.url)}"` : '';
				
				optionsHtml += `
					<button class="ai-chatbot-option ${optionClass}" ${dataAttrs} ${typeAttr} ${urlAttr}>
						${this.escapeHtml(option.text)}
					</button>
				`;
			});
			
			$('#ai-chatbot-options').html(optionsHtml);
		},
		
		clearOptions: function() {
			$('#ai-chatbot-options').html('');
		},
		
		showTyping: function() {
			$('#ai-chatbot-typing').show();
			this.scrollToBottom();
		},
		
		hideTyping: function() {
			$('#ai-chatbot-typing').hide();
		},
		
		scrollToBottom: function() {
			setTimeout(() => {
				const messagesContainer = $('#ai-chatbot-messages');
				messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
			}, 100);
		},
		
		escapeHtml: function(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, (m) => map[m]);
		}
	};
	
	// Initialize when document is ready
	$(document).ready(function() {
		Chatbot.init();
	});
	
})(jQuery);
