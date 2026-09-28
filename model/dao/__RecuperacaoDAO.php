--ou adicionar os métodos necessários dentro do LoginDAO.php já existente
--Model: model/dao/LoginDAO.php (já existe, mas precisará ser expandido com métodos de token) ou criar um método específico.

Controller: controller/LoginController.php (já existe, ideal para centralizar a lógica de envio de e-mail e redefinição).

View: view/login_redefinir.php (para a etapa final de cadastrar a nova senha após a validação do token).