***********
Módulo PagSeguro para Joomla 2.5.9 + VirtueMart 2.0.18
Este módulo tem por finalidade realizar transações de pagamentos entre sistema VirtueMart e o PagSeguro
Disponível para a versão 2.5.9 do Joomla com VirtueMart 2.0.18
***********

- Instalação

	- Na área administrativa do seu sistema, vá até o menu "Extensões > Gerenciador de Extensões";
	- Na aba Instalar aparecerá a opção "Enviar Pacote de Arquivos", onde você deverá clicar em "Selecionar Arquivo", o qual abrirá uma janela para que você selecione em seu sistema o arquivo .zip do plugin PagSeguro. Após selecionado, clique em "Upload & Instalar".
	- Nesse momento, o plugin será importado para o sistema e instalado, necessitando apenas de algumas configurações para que funcione corretamente.
	- Agora, vá até o menu "Extensões > Gerenciador de Plugins", busque por "pagseguro", selecione o módulo Pagseguro e clique na opção ativar. Agora seu plugin estará ativo para o Sistema.
	- Acesse "Componentes > VirtueMart", no menu que for exibido ao lado, clique em "Loja" e depois "Métodos de Pagamento". Clique na opção "Novo", preencha os campos com as informações que desejar e selecione para o campo "Método de Pagamento" o método de pagamento "Pagseguro". Não se esqueça de manter a opção "Publicado" como "Sim". Clique em salvar.
	- Agora, na aba "Configuração", serão exibidas as seguintes opções de configuração necessárias para o funcionamento do plugin:
		- LogoTipos (Opcional): Logotipos a serem exibidos com o nome do método de pagamento
		- email (Obrigatório): E-mail cadastrado no PagSeguro
		- token (Obrigatório): Token cadastrado no PagSeguro
		- codificação de caracteres (Obrigatório): codificação do sistema (UTF-8 ou ISO-8859-1)
		- url de redirecionamento: url utilizada para se fazer redirecionamento após o cliente realizar a efetivação da compra no ambiente do PagSeguro. Pode ser uma url do próprio sistema ou uma outra qualquer de interesse do vendedor.
		- gravar log (Opcional): define se serão gerados logs para as transações do seu sistema com o PagSeguro. Caso seja definido como "Sim" e preenchido o campo de nome do log, será gerado um log no diretório logs do sistema, caso o campo de nome do log não esteja preenchido, o log será gerado do diretório da lib do PagSeguro, dentro de /plugins/vmpayment/pagseguro/PagSeguroLibrary com o nome de PagSeguro.log.
		- Nome do arquivo de log (Opcional): Nome dado ao arquivo de log. Ex.: log_pagseguro.log. 
			
	Após esses procedimentos, seu plugin estará instalado e disponível para uso no sistema.
			
* NOTAS:
	
	- Certifique-se que o email e o token informados estejam relacionados a uma conta que possua o perfil de vendedor ou empresarial;
	- Certifique-se que tenha definido corretamente o charset de acordo com a codificação (ISO8859-1 ou UTF8) do seu sistema. Isso irá prevenir que as transações gerem possíveis erros ou quebras ou ainda que caracteres especiais possam ser apresentados de maneira diferente do habitual.
	- Para que ocorra normalmente a geração de logs pelo plugin, certifique-se que o diretório e o arquivo de log tenham permissões de leitura e escrita.
	- O PagSeguro somente aceita pagamento utilizando a moeda Real brasileiro (BRL).