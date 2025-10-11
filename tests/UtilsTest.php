<?php

registerTest('Utils::formatPhone formata números com 10 e 11 dígitos', function () {
    expectEquals('(11) 2345-6789', Utils::formatPhone('1123456789'), 'Telefone fixo deve ser formatado com 4+4');
    expectEquals('(11) 98765-4321', Utils::formatPhone('11987654321'), 'Celular deve ser formatado com 5+4');
});

registerTest('Utils::normalizePhone remove caracteres não numéricos', function () {
    expectEquals('11987654321', Utils::normalizePhone('(11) 98765-4321'), 'Normalize deve remover símbolos');
    expectEquals(null, Utils::normalizePhone(''), 'Normalize deve retornar null para vazio');
});

registerTest('Utils::sanitize limpa espaços e caracteres especiais', function () {
    expectEquals('Acme &amp; Co', Utils::sanitize(' Acme & Co '), 'Sanitize deve trim e escapar HTML');
});
