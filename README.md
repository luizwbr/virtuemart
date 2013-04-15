Módulo de integração PagSeguro para VirtueMart
==============================================
---
Descrição
---------
---
Com o módulo instalado e configurado, você pode pode oferecer o PagSeguro como opção de pagamento em sua loja. O módulo utiliza as seguintes funcionalidades que o PagSeguro oferece na forma de APIs:

 - Integração com a [API de Pagamentos]


Requisitos
----------
---
 - [Joomla] 2.5.9
 - [VirtueMart] 2.0.18
 - [PHP] 5.1.6+
 - [SPL]
 - [cURL]
 - [DOM]


Instalação
----------
---
 - Certifique-se de que não há instalação de outros módulos para o PagSeguro em seu sistema;
 - Baixe o repositório como arquivo zip ou faça um clone;
 - Na área administrativa do seu sistema, acesse o menu Extension > Extension Manager, no campo Package File aponte para o caminho do arquivo .zip que foi baixado e em seguida selecione Upload & Install;
 - Acesse o menu Extension -> Plugin Manager, localize e selecione o módulo PagSeguro e em seguida defina o Status como Enabled.


Configuração
------------
---
Para acessar e configurar o módulo acesse o menu Components -> VirtueMart -> Shop -> Payment Methods -> New, preencha os dados conforme modelo a seguir:

 - **Payment Name**: PagSeguro.
 - **Published**: Yes.
 - **Payment Description**: Pague com PagSeguro e parcele em até 18 vezes.
 - **Payment Method**: PagSeguro.
 - **Shopper Group**: Aqui você deve definir os grupos de clientes que poderão pagar usando o PagSeguro.
 - **List Order**: Aqui você deve definir a ordem em que o PagSeguro será exibido no checkout de sua loja.

Salve as configurações e clique na aba Configuration. As opções disponíveis estão descritas abaixo.

 - **e-mail**: e-mail cadastrado no PagSeguro.
 - **token**: token cadastrado no PagSeguro.
 - **url de redirecionamento**: ao final do fluxo de pagamento no PagSeguro, seu cliente será redirecionado automaticamente para a página de confirmação em sua loja ou então para a URL que você informar neste campo. Para ativar o redirecionamento ao final do pagamento é preciso ativar o serviço de [Pagamentos via API].
 - **charset**: codificação do seu sistema (ISO-8859-1 ou UTF-8).
 - **log**: ativa/desativa a geração de logs.
 - **diretório**: informe o local a partir da raíz de instalação do Joomla onde se deseja criar o arquivo de log. Ex.: /logs/ps.log. Caso não informe nada, o log será gravado dentro da pasta ../PagSeguroLibrary/PagSeguro.log.

Opcionalmente você pode mudar o relacionamento feito entre os status de pagamento no PagSeguro com os status de pagamento dentro de sua loja. Para mais informações, consulte a [máquina de estados] que descreve os status de transação no PagSeguro e as possíveis transições entre eles.

Changelog
---------
---
1.0

 - Versão inicial. Integração com API de checkout do PagSeguro.


Licença
-------
---
Copyright 2013 PagSeguro Internet LTDA.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.


Notas
-----
---
 - O PagSeguro somente aceita pagamento utilizando a moeda Real brasileiro (BRL).
 - Certifique-se que o email e o token informados estejam relacionados a uma conta que possua o perfil de vendedor ou empresarial.
 - Certifique-se que tenha definido corretamente o charset de acordo com a codificação (ISO-8859-1 ou UTF-8) do seu sistema. Isso irá prevenir que as transações gerem possíveis erros ou quebras ou ainda que caracteres especiais possam ser apresentados de maneira diferente do habitual.
 - Para que ocorra normalmente a geração de logs, certifique-se que o diretório e o arquivo de log tenham permissões de leitura e escrita.


[Dúvidas?]
----------
---
Mande um [e-mail] ou acesse o [fórum] de discussões.


Contribuições
-------------
---
Achou e corrigiu um bug ou tem alguma feature em mente e deseja contribuir?

* Faça um fork.
* Adicione sua feature ou correção de bug.
* Envie um pull request no GitHub.


  [API de Pagamentos]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-pagamentos.html
  [e-mail]: <mailto:desenvolvedores@pagseguro.com.br>
  [fórum]: http://forum.imasters.com.br/forum/244-gateways-e-meios-de-pagamento-online-pagseguro
  [Dúvidas?]: https://pagseguro.uol.com.br/desenvolvedor/comunidade.jhtml
  [Pagamentos via API]: https://pagseguro.uol.com.br/integracao/pagamentos-via-api.jhtml
  [Notificação de Transações]: https://pagseguro.uol.com.br/integracao/notificacao-de-transacoes.jhtml
  [máquina de estados]: https://pagseguro.uol.com.br/v2/guia-de-integracao/api-de-notificacoes.html#v2-item-api-de-notificacoes-status-da-transacao
  [Joomla]: http://www.joomla.org/
  [VirtueMart]: http://virtuemart.net/
  [PHP]: http://www.php.net/
  [SPL]: http://php.net/manual/en/book.spl.php
  [cURL]: http://php.net/manual/en/book.curl.php
  [DOM]: http://php.net/manual/en/book.dom.php
