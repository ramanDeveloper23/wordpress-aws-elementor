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
				const optionText = $(this).text().trim(); // Get text before clearing
				
				if (optionType === 'link' && optionUrl) {
					// Handle link option
					Chatbot.handleLinkOption(questionId, optionUrl, optionText);
				} else {
					// Handle regular option
					Chatbot.handleOptionClick(questionId, optionText);
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
				
				// Clear previous options
				this.clearOptions();
				
				// Show typing indicator
				this.showTyping();
				
				// Get response via AJAX (user message already added above)
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
							
							// Add bot message (as text, not a button)
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
							
							// Display options as buttons below the message
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
			} else {
				// Show typing and provide helpful response
				this.showTyping();
				setTimeout(() => {
					this.hideTyping();
					// Provide context-aware response
					let responseMessage = 'I understand you\'re interested. ';
					const lowerMessage = message.toLowerCase();
					
					if (lowerMessage.includes('makeup') || lowerMessage.includes('beauty')) {
						responseMessage += 'We offer both bridal makeup services and makeup classes. Would you like to learn about our services or our classes?';
					} else {
						responseMessage += 'How can I help you today? You can ask about our bridal makeup services, makeup classes, pricing, or book a consultation.';
					}
					
					this.addMessage(responseMessage, 'bot');
					// Reload initial options
					if (aiChatbot.initialOptions && aiChatbot.initialOptions.length > 0) {
						this.displayOptions(aiChatbot.initialOptions);
					} else {
						// Load options via AJAX without adding another message
						$.ajax({
							url: aiChatbot.ajaxUrl,
							type: 'POST',
							data: {
								action: 'ai_chatbot_response',
								question_id: 'greeting',
								nonce: aiChatbot.nonce
							},
							success: (response) => {
								if (response.success && response.data && response.data.options) {
									this.displayOptions(response.data.options);
								}
							}
						});
					}
				}, 1000);
			}
		},
		
		matchMessageToOption: function(message) {
			const lowerMessage = message.toLowerCase();
			
			// Match bridal makeup learning queries (e.g., "learning bridal makeup techniques")
			if ((lowerMessage.includes('bridal') || lowerMessage.includes('wedding') || lowerMessage.includes('bride')) && 
				(lowerMessage.includes('learn') || lowerMessage.includes('learning') || lowerMessage.includes('class') || 
				 lowerMessage.includes('course') || lowerMessage.includes('training') || lowerMessage.includes('technique'))) {
				return { id: 'bridal_learning', type: null, url: null };
			}
			
			// Match bridal makeup services (not learning)
			if (lowerMessage.includes('bridal') || lowerMessage.includes('wedding') || lowerMessage.includes('bride')) {
				return { id: 'bridal_makeup', type: null, url: null };
			}
			
			// Match learning makeup classes
			if (lowerMessage.includes('learn') || lowerMessage.includes('learning') || lowerMessage.includes('class') || 
				lowerMessage.includes('course') || lowerMessage.includes('training') || lowerMessage.includes('masterclass') ||
				lowerMessage.includes('technique') || lowerMessage.includes('techniques')) {
				return { id: 'learn_makeup', type: null, url: null };
			}
			
			// Match pricing queries
			if (lowerMessage.includes('price') || lowerMessage.includes('cost') || lowerMessage.includes('pricing') ||
				lowerMessage.includes('how much') || lowerMessage.includes('fee') || lowerMessage.includes('charge')) {
				return { id: 'pricing', type: null, url: null };
			}
			
			// Match contact/booking queries
			if (lowerMessage.includes('contact') || lowerMessage.includes('book') || lowerMessage.includes('appointment') ||
				lowerMessage.includes('schedule') || lowerMessage.includes('available') || lowerMessage.includes('dates')) {
				return { id: 'contact', type: null, url: null };
			}
			
			// Match intermediate/advanced queries
			if (lowerMessage.includes('intermediate') || lowerMessage.includes('advanced') || lowerMessage.includes('masterclass')) {
				return { id: 'intermediate_masterclass', type: null, url: null };
			}
			
			return null;
		},
		
		loadInitialOptions: function() {
			// Do NOT display initial options - user must type a message first
			// Options will only appear after user interaction
			this.clearOptions();
		},
		
		handleOptionClick: function(questionId, optionText = null) {
			// Get option text if not provided
			if (!optionText) {
				optionText = $('.ai-chatbot-option[data-question-id="' + questionId + '"]').text().trim();
			}
			
			// Clear previous options
			this.clearOptions();
			
			// Add user message with the option text
			if (optionText) {
				this.addMessage(optionText, 'user');
			}
			
			// Show typing indicator
			this.showTyping();
			
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
		
		handleLinkOption: function(questionId, url, optionText = null) {
			// Get option text if not provided
			if (!optionText) {
				optionText = $('.ai-chatbot-option[data-question-id="' + questionId + '"]').text().trim();
			}
			
			// Clear previous options
			this.clearOptions();
			
			// Add user message
			if (optionText) {
				this.addMessage(optionText, 'user');
			}
			
			// Show typing indicator
			this.showTyping();
			
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
