<?php
/**
 * Plugin Name: WPForo to Discord
 * Description: Sends new wpForo topics to a Discord channel via webhook (v2.4.6 compatible, embed style). Skips excluded forums.
 * Version: 2.2
 * Author: Teo
 */

if (!defined('ABSPATH')) exit;

// Load admin page
add_action('admin_menu', function(){
    add_options_page(
        'WPForo to Discord',
        'WPForo → Discord',
        'manage_options',
        'wpforo-to-discord',
        'wpforo_to_discord_settings_page'
    );
});

require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';

// --- New Topic → Discord ---
add_action('wpforo_after_add_topic', function($topic){
    if (empty($topic['topicid'])) return;

    $webhook_url = get_option('wpf2d_webhook_url', '');
    if (!$webhook_url) return;

    $excluded_ids = get_option('wpf2d_excluded_forums', '');
    $excluded = array_filter(array_map('intval', explode(',', $excluded_ids)));

    $topicid = (int) $topic['topicid'];

    // Get topic data
    $full = [];
    if (function_exists('wpforo_topic')) {
        $t = wpforo_topic($topicid);
        if (is_array($t)) $full = $t;
    }
    if (!$full) {
        global $wpdb;
        $full = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpforo_topics WHERE topicid = %d", $topicid),
            ARRAY_A
        );
    }
    if (!$full) return;

    // Skip excluded forums
    if (!empty($full['forumid']) && in_array((int)$full['forumid'], $excluded, true)) {
        error_log("[WPForo→Discord] Skipped topic {$topicid} in excluded forum {$full['forumid']}");
        return;
    }

    $title  = $full['title'] ?? ($topic['title'] ?? 'Untitled');
    $author = 'Guest';

    if (!empty($full['userid'])) {
        if (function_exists('WPF') && isset(WPF()->member)) {
            $member = WPF()->member->get_member((int)$full['userid']);
            $author = $member['display_name'] ?? $member['user_nicename'] ?? '';
        }
        if (!$author) {
            $u = get_userdata((int)$full['userid']);
            $author = $u ? ($u->display_name ?: $u->user_login) : 'Guest';
        }
    } elseif (!empty($full['name'])) {
        $author = $full['name'];
    }

    $forum_name = '';
    if (!empty($full['forumid']) && function_exists('WPF') && isset(WPF()->forum)) {
        $forum = WPF()->forum->get_forum($full['forumid']);
        if (!empty($forum['title'])) $forum_name = $forum['title'];
    }

    // URL
    $url = '';
    if (function_exists('WPF') && isset(WPF()->topic) && method_exists(WPF()->topic, 'get_topic_url')) {
        $url = WPF()->topic->get_topic_url($topicid);
    }
    if (!$url && function_exists('wpforo_topic_url')) {
        $url = wpforo_topic_url($topicid);
    }
    if (!$url && !empty($full['first_postid']) && function_exists('wpforo_post_url')) {
        $url = wpforo_post_url((int)$full['first_postid']);
    }
    if (!$url && !empty($full['url'])) {
        $url = $full['url'];
    }
    if (!$url) {
        if (function_exists('wpforo_home_url')) {
            $url = add_query_arg(['wpforo' => 'topic', 'topicid' => $topicid], wpforo_home_url());
        } else {
            $url = home_url(add_query_arg(['wpforo' => 'topic', 'topicid' => $topicid], '/'));
        }
    }

    // Excerpt
    $excerpt = '';
    if (!empty($full['first_postid']) && function_exists('WPF') && isset(WPF()->post) && method_exists(WPF()->post, 'get_post')) {
        $first_post = WPF()->post->get_post((int)$full['first_postid']);
        if (is_array($first_post) && !empty($first_post['body'])) {
            $raw = wp_strip_all_tags($first_post['body']);
            $raw = preg_replace('/\s+/', ' ', $raw);
            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                $excerpt = (mb_strlen($raw) > 180) ? mb_substr($raw, 0, 179) . '…' : $raw;
            } else {
                $excerpt = (strlen($raw) > 180) ? substr($raw, 0, 179) . '…' : $raw;
            }
        }
    }
    if (!$excerpt) {
        $excerpt = "New topic created in **{$forum_name}**";
    }

    // Decode HTML entities
    $title   = html_entity_decode($title,   ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $author  = html_entity_decode($author,  ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $excerpt = html_entity_decode($excerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Embed
    $embed = [
        'title'       => $title,
        'url'         => $url,
        'description' => $excerpt,
        'color'       => hexdec('5865F2'),
        'footer'      => [
            'text' => strtoupper($author),
            'icon_url' => function_exists('get_site_icon_url') ? get_site_icon_url() : ''
        ],
        'timestamp'   => gmdate('c')
    ];

    $payload = json_encode(['embeds' => [ $embed ]], JSON_UNESCAPED_UNICODE);

    $res = wp_remote_post($webhook_url, [
        'body'    => $payload,
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 8,
    ]);

    if (is_wp_error($res)) {
        error_log('WPForo→Discord ERROR: '.$res->get_error_message());
    }
}, 10, 1);
