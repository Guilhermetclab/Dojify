// ==========================================
// NOTIFICAÇÕES (MENU DROPDOWN DO HEADER)
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const btnNotificacao = document.getElementById('btnNotificacao');
    const dropdownNotificacoes = document.getElementById('dropdownNotificacoes');

    if (btnNotificacao && dropdownNotificacoes) {
        btnNotificacao.addEventListener('click', function (e) {
            e.stopPropagation(); // Evita que o clique feche imediatamente
            dropdownNotificacoes.classList.toggle('show');
        });

        // Clica fora do menu para fechar
        document.addEventListener('click', function (e) {
            if (!dropdownNotificacoes.contains(e.target) && e.target !== btnNotificacao) {
                dropdownNotificacoes.classList.remove('show');
            }
        });
    }
});


// ==========================================
// MENSAGEM DE SUCESSO OU ERRO
// ==========================================

function mostrarMensagem(tipo, mensagem) {
    const mensagemExistente = document.querySelector('.toast');

    if (mensagemExistente) {
        mensagemExistente.remove();
    }

    const toast = document.createElement('div');

    toast.className = 'toast toast--' + tipo;
    toast.textContent = mensagem;

    document.body.appendChild(toast);

    setTimeout(function () {
        toast.classList.add('toast-show');
    }, 100);

    setTimeout(function () {
        toast.classList.remove('toast-show');

        setTimeout(function () {
            toast.remove();
        }, 300);

    }, 4000);
}


// ==========================================
// CONFIRMAÇÃO DE AÇÃO
// ==========================================

document.addEventListener('click', function (event) {
    const elemento = event.target.closest('[data-confirmar]');

    if (!elemento) {
        return;
    }

    const mensagem = elemento.getAttribute('data-confirmar');

    if (!confirm(mensagem)) {
        event.preventDefault();
    }
});


// ==========================================
// MENSAGENS VINDAS DA URL
// ==========================================

document.addEventListener('DOMContentLoaded', function () {
    const parametros = new URLSearchParams(window.location.search);

    const sucesso = parametros.get('sucesso');
    const erro = parametros.get('erro');

    if (sucesso) {
        let mensagem = 'Operação realizada com sucesso!';

        switch (sucesso) {
            case 'cadastrado':
                mensagem = 'Cadastro realizado com sucesso!';
                break;
            case 'professor_cadastrado':
                mensagem = 'Professor cadastrado com sucesso!';
                break;
            case 'atualizado':
                mensagem = 'Dados atualizados com sucesso!';
                break;
            case 'recebimento_registado':
                mensagem = 'Recebimento confirmado com sucesso!';
                break;
            case 'excluido':
                mensagem = 'Registro excluído com sucesso!';
                break;
        }

        mostrarMensagem('sucesso', mensagem);
    }

    if (erro) {
        let mensagem = 'Não foi possível realizar a operação.';

        switch (erro) {
            case 'falha_cadastro':
                mensagem = 'Não foi possível realizar o cadastro.';
                break;
            case 'falha_recebimento':
                mensagem = 'Não foi possível confirmar o recebimento.';
                break;
            case 'cpf_duplicado':
                mensagem = 'Este CPF já está cadastrado.';
                break;
        }

        mostrarMensagem('erro', mensagem);
    }
});