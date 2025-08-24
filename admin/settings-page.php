<?php 
if (!defined('ABSPATH')) exit;

function wpforo_to_discord_settings_page() {
    // Carica i dati salvati
    $webhook_url     = get_option('wpf2d_webhook_url', '');
    $excluded_forums = get_option('wpf2d_excluded_forums', '');
    $excluded        = array_filter(array_map('intval', explode(',', $excluded_forums)));

    // Gestione form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer('wpf2d_settings');

        // Ricarico stato attuale
        $excluded_forums = get_option('wpf2d_excluded_forums', '');
        $excluded = array_filter(array_map('intval', explode(',', $excluded_forums)));

        // ---- Delete forum ----
        if (isset($_POST['wpf2d_delete_forum'])) {
            $fid = (int) $_POST['wpf2d_delete_forum'];
            $excluded = array_diff($excluded, [$fid]);
            update_option('wpf2d_excluded_forums', implode(',', $excluded));
            echo '<div class="updated"><p>Forum removed from exclusions.</p></div>';
        }

        // ---- Save settings / Add forum ----
        elseif (isset($_POST['wpf2d_save_settings'])) {
            // Salvo webhook
            update_option('wpf2d_webhook_url', sanitize_text_field($_POST['wpf2d_webhook_url']));

            // Aggiungo forum escluso
            if (!empty($_POST['wpf2d_add_forum'])) {
                $fid = (int) $_POST['wpf2d_add_forum'];
                if ($fid > 0 && !in_array($fid, $excluded, true)) {
                    $excluded[] = $fid;
                }
            }

            // Salvo esclusioni aggiornate
            update_option('wpf2d_excluded_forums', implode(',', $excluded));

            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // Aggiorno variabili dopo salvataggio
        $webhook_url     = get_option('wpf2d_webhook_url', '');
        $excluded_forums = get_option('wpf2d_excluded_forums', '');
        $excluded        = array_filter(array_map('intval', explode(',', $excluded_forums)));
    }

    // Recupero lista forum
    $all_forums = [];
    if (function_exists('WPF') && isset(WPF()->forum)) {
        $all_forums = WPF()->forum->get_forums();
    }
    ?>
    <div class="wrap">
        <h1>WPForo to Discord</h1>
        <form method="post">
            <?php wp_nonce_field('wpf2d_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wpf2d_webhook_url">Discord Webhook URL</label></th>
                    <td>
                        <input type="text" id="wpf2d_webhook_url" name="wpf2d_webhook_url" value="<?php echo esc_attr($webhook_url); ?>" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Excluded Forums</th>
                    <td>
                        <div style="margin-bottom:10px;">
                            <select name="wpf2d_add_forum">
                                <option value="">-- Select forum --</option>
                                <?php foreach ($all_forums as $f): ?>
                                    <?php if (!in_array((int)$f['forumid'], $excluded, true)): ?>
                                        <option value="<?php echo (int)$f['forumid']; ?>">
                                            <?php echo esc_html($f['title']); ?> (ID: <?php echo (int)$f['forumid']; ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="wpf2d_save_settings" class="button">Add</button>
                        </div>

                        <?php if ($excluded): ?>
                            <ul style="list-style:disc; margin-left:20px;">
                                <?php foreach ($excluded as $fid): ?>
                                    <?php
                                    $fname = '';
                                    foreach ($all_forums as $f) {
                                        if ((int)$f['forumid'] === $fid) $fname = $f['title'];
                                    }
                                    ?>
                                    <li>
                                        <?php echo esc_html($fname ?: "Forum #$fid"); ?> (ID: <?php echo $fid; ?>)
                                        <button type="submit" name="wpf2d_delete_forum" value="<?php echo $fid; ?>" class="button-link-delete">Delete</button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p><em>No forums excluded.</em></p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="wpf2d_save_settings" class="button button-primary" value="Save Settings" />
            </p>
        </form>
    </div>
    <?php
}
