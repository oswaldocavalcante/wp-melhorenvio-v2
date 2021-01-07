<?php

namespace Models;

class ShippingService
{
    const SERVICES_CORREIOS = [1, 2, 17];

    const SERVICES_JADLOG = [3, 4];

    const SERVICES_AZUL = [15, 16];

    const SERVICES_VIA_BRASIL = [9];

    const CORREIOS_PAC = 1;

    const CORREIOS_SEDEX = 2;

    const JADLOG_PACKAGE = 3;

    const JADLOG_COM = 4;

    const VIA_BRASIL_AERO = 8;

    const VIA_BRASIL_RODOVIARIO = 9;

    const AZUL_AMANHA = 15;

    const AZUL_ECOMMERCE = 16;

    const CORREIOS_MINI = 17;

    const OPTIONS_SHIPPING_SERVICES = 'shipping_services_melhor_envio';

    /**
     * Function to return avalaible services.
     *
     * @return array
     */
    public static function getAvailableServices()
    {
        return array_merge(
            self::SERVICES_CORREIOS,
            self::SERVICES_JADLOG,
            self::SERVICES_AZUL,
            self::SERVICES_VIA_BRASIL
        );
    }

    /**
     * Function to converter method_id to code Melhor envio.
     *
     * @param $methodId
     * @return int
     */
    public static function getCodeByMethodId($methodId)
    {
        switch ($methodId) {
        case "melhorenvio_correios_pac":
            return self::CORREIOS_PAC;
        case "melhorenvio_correios_sedex":
            return self::CORREIOS_SEDEX;
        case "melhorenvio_jadlog_package":
            return self::JADLOG_PACKAGE;
        case "melhorenvio_jadlog_com":
            return self::JADLOG_COM;
        case "melhorenvio_via_brasil_aero":
            return self::VIA_BRASIL_AERO;
        case "melhorenvio_via_brasil_rodoviario":
            return self::VIA_BRASIL_RODOVIARIO;
        case "melhorenvio_azul_amanha":
            return self::AZUL_AMANHA;
        case "melhorenvio_azul_ecommerce":
            return self::AZUL_ECOMMERCE;
        case "melhorenvio_correios_mini":
            return self::CORREIOS_MINI;
        default:
            return self::CORREIOS_SEDEX;
        }
    }


    /**
     * function to save shipping services.
     *
     * @param array $shippingServices
     * @return int
     */
    public function save($shippingServices)
    {
        delete_option(self::OPTIONS_SHIPPING_SERVICES);
        return add_option(self::OPTIONS_SHIPPING_SERVICES, $shippingServices, '', true);
    }

    /**
     * function to get shipping services.
     *
     * @return array
     */
    public function get()
    {
        return get_option(self::OPTIONS_SHIPPING_SERVICES);
    }

    /**
     * function to delete shipping services.
     *
     * @return bool
     */
    public function destroy()
    {
        return delete_option(self::OPTIONS_SHIPPING_SERVICES);
    }
}
