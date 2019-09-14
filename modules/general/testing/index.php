<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (zb_TariffProtected('Dorogo')) {
    deb('Tariff is protected');
} else {
    deb('Tariff can be deleted');
}