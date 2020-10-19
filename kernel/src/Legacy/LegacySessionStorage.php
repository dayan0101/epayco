<?php

namespace ePayco\Kernel\Legacy;

use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Clase de compatibilidad entre el manejo de sesiones de la libreria Symfony y el manejo basico de sesiones. Esto, con el fin de poder interactuar las aplicaciones antiguas con las creadas.
 **/
class LegacySessionStorage extends NativeSessionStorage
{

    /**
     * @inheritDoc
     */
    public function __construct(array $options = array(), $handler = null, MetadataBag $metaBag = null)
    {
        parent::__construct($options, $handler, $metaBag);
    }

    /**
     * @inheritDoc
     */
    protected function loadSession(array &$session = null)
    {
        if (null === $session) {
            $session = &$_SESSION;
        }

        foreach ($this->bags as $bag) {
            if ($bag instanceof FlashBagInterface or $bag instanceof MetadataBag) {
                $key = $bag->getStorageKey();
                $session[$key] = isset($session[$key]) ? $session[$key] : array();
                $bag->initialize($session[$key]);
            } else {
                $bag->initialize($session);
            }
        }

        //metadataBag

        $this->started = true;
        $this->closed = false;
    }
}


