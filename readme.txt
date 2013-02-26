***********
Módulo de integração PagSeguro para VirtueMart
v.1.0
***********


= Descrição =

Este módulo tem por finalidade integrar o PagSeguro como meio de pagamento dentro da plataforma VirtueMart.


= Requisitos =

Joomla 2.5.9 + VirtueMart 2.0.18


= Instalação =

1. Na área administrativa do seu sistema, vá até o menu "Extensões > Gerenciador de Extensões";
2. Na aba Instalar aparecerá a opção "Enviar Pacote de Arquivos", onde você deverá clicar em "Selecionar Arquivo", o qual abrirá uma janela para que você selecione em seu sistema o arquivo .zip do plugin PagSeguro. Após selecionado, clique em "Upload & Instalar";
3. Nesse momento, o plugin será importado para o sistema e instalado, necessitando apenas de algumas configurações para que funcione corretamente;
4. Agora, vá até o menu "Extensões > Gerenciador de Plugins", busque por "pagseguro", selecione o módulo PagSeguro e clique na opção ativar. Agora seu plugin estará ativo para o Sistema.


= Configuração =

1. Acesse "Componentes > VirtueMart", no menu que for exibido ao lado, clique em "Loja" e depois "Métodos de Pagamento". Clique na opção "Novo", preencha os campos com as informações que desejar e selecione para o campo "Método de Pagamento" o método de pagamento "PagSeguro". Não se esqueça de manter a opção "Publicado" como "Sim". Clique em salvar;
2. Agora, na aba "Configuração", serão exibidas as seguintes opções de configuração necessárias para o funcionamento do plugin:
2.1. logotipos (Opcional): Logotipos a serem exibidos com o nome do método de pagamento
2.2. email (Obrigatório): E-mail cadastrado no PagSeguro
2.3. token (Obrigatório): Token cadastrado no PagSeguro
2.4. codificação de caracteres (Obrigatório): codificação do sistema (UTF-8 ou ISO-8859-1)
2.5. url de redirecionamento: url utilizada para se fazer redirecionamento após o cliente realizar a efetivação da compra no ambiente do PagSeguro. Pode ser uma url do próprio sistema ou uma outra qualquer de interesse do vendedor
2.6. gravar log (Opcional): define se serão gerados logs para as transações do seu sistema com o PagSeguro. Caso seja definido como "Sim" e preenchido o campo de nome do log, será gerado um log no diretório logs do sistema, caso o campo de nome do log não esteja preenchido, o log será gerado do diretório da lib do PagSeguro, dentro de /plugins/vmpayment/pagseguro/PagSeguroLibrary com o nome de PagSeguro.log
2.7. Nome do arquivo de log (Opcional): Nome dado ao arquivo de log. Ex.: log_pagseguro.log


= Changelog =

v1.0
Versão inicial. Integração com API de checkout do PagSeguro.


= NOTAS =
	
	- Certifique-se que o email e o token informados estejam relacionados a uma conta que possua o perfil de vendedor ou empresarial.
	- Certifique-se que tenha definido corretamente o charset de acordo com a codificação (ISO-8859-1 ou UTF-8) do seu sistema. Isso irá prevenir que as transações gerem possíveis erros ou quebras ou ainda que caracteres especiais possam ser apresentados de maneira diferente do habitual.
	- Para que ocorra normalmente a geração de logs pelo plugin, certifique-se que o diretório e o arquivo de log tenham permissões de leitura e escrita.
	- O PagSeguro somente aceita pagamento utilizando a moeda Real brasileiro (BRL).