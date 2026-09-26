<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Aluno - Dojify</title>
    <link rel="stylesheet" href="../../assets/css/estilo.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <form action="../../controller/UsuarioController.php?acao=cadastrar_aluno" method="POST">
            <div class="text-center" style="margin-bottom: 20px;">
                <img src="../../assets/img/dojify_logo1.png" alt="Dojify Logo" style="width: 100px; height: auto; margin-bottom: 12px; filter: grayscale(100%);">
                <h2>Cadastrar Novo Aluno</h2>
            </div>
            
            <div>
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: João da Silva" required>
            </div>

            <div>
                <label for="cpf">CPF:</label>
                <input type="text" id="cpf" name="cpf" placeholder="Apenas números" required>
            </div>

            <div>
                <label for="data_nascimento">Data de Nascimento:</label>
                <input type="date" id="data_nascimento" name="data_nascimento" required>
            </div>

            <div>
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" placeholder="Ex: (11) 98888-7777" required>
            </div>

            <div>
                <label for="email">E-mail de Acesso:</label>
                <input type="email" id="email" name="email" placeholder="exemplo@email.com" required>
            </div>

            <div>
                <label for="senha">Senha temporária de Acesso:</label>
                <input type="password" id="senha" name="senha" placeholder="Mínimo de 6 caracteres" required>
            </div>

        <div>
                <label for="data_matricula">Data da Matrícula:</label>
                <input type="date" id="data_matricula" name="data_matricula" required>
            </div>

            <div>
                <label for="responsavel">Responsável Legal (Caso seja menor):</label>
                <input type="text" id="responsavel" name="responsavel" placeholder="Nome do pai ou responsável">
            </div>

            <div>
                <label for="observacao">Observações Médicas / Alergias:</label>
                <textarea id="observacao" name="observacao" placeholder="Ex: Alérgico a dipirona, lesão antiga no joelho..."></textarea>
            </div> 
            
            <div>
                <label for="nome_plano">Plano Contratado:</label>
                <select id="nome_plano" name="nome_plano" required>
                    <option value="">Selecione o plano...</option>
                    
                    <optgroup label="Planos Mensais">
                        <option value="Mensal 2x/semana">Mensal (2x/semana)</option>
                        <option value="Mensal 3x/semana">Mensal (3x/semana)</option>
                        <option value="Mensal Ilimitado">Mensal (Ilimitado)</option>
                    </optgroup>

                    <optgroup label="Planos Trimestrais">
                        <option value="Trimestral 2x/semana">Trimestral (2x/semana)</option>
                        <option value="Trimestral 3x/semana">Trimestral (3x/semana)</option>
                        <option value="Trimestral Ilimitado">Trimestral (Ilimitado)</option>
                    </optgroup>

                    <optgroup label="Planos Anuais">
                        <option value="Anual 2x/semana">Anual (2x/semana)</option>
                        <option value="Anual 3x/semana">Anual (3x/semana)</option>
                        <option value="Anual Ilimitado">Anual (Ilimitado)</option>
                    </optgroup>
                </select>
            </div>
            <div>
                <label for="valor_plano">Valor do Plano (R$):</label>
                <input type="number" step="0.01" min="0" id="valor_plano" name="valor_plano" placeholder="Ex: 150.00" required>
            </div>

            <button type="submit">Concluir Matrícula</button>
        </form>
    </div>

    <?php include '../includes/footer.php'; ?>

</body>
</html>