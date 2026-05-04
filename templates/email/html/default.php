<?php

/**
 * Default HTML Email Template
 *
 * Default template for HTML email content formatting. Converts plain text content
 * into properly formatted HTML paragraphs for email clients.
 *
 * Features:
 * - Line-by-line content processing
 * - Automatic paragraph creation from text lines
 * - HTML-safe content rendering
 * - Email client compatibility
 *
 * Content Processing:
 * - Splits content by newlines
 * - Wraps each line in <p> tags
 * - Maintains text formatting structure
 * - Safe HTML rendering with proper escaping
 *
 * Usage:
 * - Used automatically by CakePHP Email component
 * - Handles plain text to HTML conversion
 * - Provides consistent email formatting
 *
 * Email Client Compatibility:
 * - Simple HTML structure for broad support
 * - Paragraph-based layout
 * - No complex CSS dependencies
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var \App\View\AppView $this
 * @var string $content Email content to be formatted
 */

$lines = explode("\n", $content);

foreach ($lines as $line) :
    echo '<p> ' . $line . "</p>\n";
endforeach;
