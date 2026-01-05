<?php
if (!defined('ABSPATH')) exit;

final class BRLGPD_REST
{
    public static function init(): void
    {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes(): void
    {
        // MVP: sem rotas públicas para evitar superfícies desnecessárias.
        // Futuro: rotas somente leitura para status, com cuidado.
    }
}
