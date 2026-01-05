<?php
if (!defined('ABSPATH')) exit;

/** @var array $data */
$title = $data['title'] ?? '';
$message = $data['message'] ?? '';
$btn_accept = $data['btn_accept'] ?? '';
$btn_reject = $data['btn_reject'] ?? '';
$btn_prefs  = $data['btn_prefs'] ?? '';
$modal_title = $data['modal_title'] ?? '';
$modal_desc  = $data['modal_desc'] ?? '';
$hide_banner = !empty($data['hide_banner']);
$categories = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];
$choices = isset($data['choices']) && is_array($data['choices']) ? $data['choices'] : [];
?>

<?php if (!$hide_banner): ?>
  <div id="brlgpd-banner" role="dialog" aria-live="polite" aria-label="<?php echo esc_attr($title); ?>">
    <h4><?php echo esc_html($title); ?></h4>
    <p><?php echo wp_kses_post($message); ?></p>

    <?php if (!empty($data['show_policy_link']) && !empty($data['policy_url'])): ?>
      <p style="margin-top:8px; font-size:13px;" class="brlgpd-policy-link">
        <a href="<?php echo esc_url($data['policy_url']); ?>">
          <?php echo esc_html($data['policy_link_text'] ?? 'Política de Cookies'); ?>
        </a>
      </p>
    <?php endif; ?>

    <div class="brlgpd-actions">
      <button type="button" class="brlgpd-btn brlgpd-btn-primary" data-brlgpd-action="accept">
        <?php echo esc_html($btn_accept); ?>
      </button>
      <button type="button" class="brlgpd-btn brlgpd-btn-secondary" data-brlgpd-action="reject">
        <?php echo esc_html($btn_reject); ?>
      </button>
      <button type="button" class="brlgpd-btn" data-brlgpd-action="open">
        <?php echo esc_html($btn_prefs); ?>
      </button>
    </div>
  </div>
<?php endif; ?>

<div id="brlgpd-modal-overlay" class="brlgpd-hidden" aria-hidden="true">
  <div id="brlgpd-modal" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($modal_title); ?>">
    <header>
      <div>
        <h4><?php echo esc_html($modal_title); ?></h4>
        <p class="brlgpd-muted"><?php echo wp_kses_post($modal_desc); ?></p>
      </div>
      <button id="brlgpd-close" type="button" aria-label="<?php echo esc_attr__('Fechar', 'br-lgpd-consent'); ?>" data-brlgpd-action="close">×</button>
    </header>

    <div class="brlgpd-categories">
      <?php foreach ($categories as $cat):
        $key = (string)($cat['key'] ?? '');
        $name = (string)($cat['name'] ?? $key);
        $desc = (string)($cat['desc'] ?? '');
        $always = !empty($cat['always_active']);
        if ($key === '') continue;
      ?>
        <div class="brlgpd-cat">
          <div class="brlgpd-cat-title">
            <strong><?php echo esc_html($name); ?></strong>

            <?php if ($always): ?>
              <span class="brlgpd-toggle">
                <input type="checkbox" checked disabled />
                <?php echo esc_html__('Sempre ativos', 'br-lgpd-consent'); ?>
              </span>
            <?php else: ?>
              <label class="brlgpd-toggle">
                <input type="checkbox" data-brlgpd-cat="<?php echo esc_attr($key); ?>" <?php checked(true, !empty($choices[$key])); ?> />
                <?php echo esc_html__('Permitir', 'br-lgpd-consent'); ?>
              </label>
            <?php endif; ?>
          </div>

          <?php if ($desc !== ''): ?>
            <p><?php echo esc_html($desc); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="brlgpd-actions">
      <button type="button" class="brlgpd-btn brlgpd-btn-primary" data-brlgpd-action="save">
        <?php echo esc_html__('Salvar preferências', 'br-lgpd-consent'); ?>
      </button>
      <button type="button" class="brlgpd-btn" data-brlgpd-action="accept">
        <?php echo esc_html__('Aceitar tudo', 'br-lgpd-consent'); ?>
      </button>
      <button type="button" class="brlgpd-btn brlgpd-btn-secondary" data-brlgpd-action="reject">
        <?php echo esc_html__('Rejeitar', 'br-lgpd-consent'); ?>
      </button>
    </div>
  </div>
</div>
