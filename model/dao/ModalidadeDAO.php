<?php
//model/dao/ModalidadeDAO.php
require_once __DIR__ . '/Conexao.php';
require_once __DIR__ . '/../dto/ModalidadeDTO.php';

class ModalidadeDAO {
    private PDO $conexao;

    public function __construct() {
        $this->conexao = \Conexao::getConexao();
    }

    public function cadastrar(\ModalidadeDTO $modalidade): bool {
        try {
            $sql = "INSERT INTO modalidade (id_academia, nome, descricao) 
                    VALUES (:id_academia, :nome, :descricao)";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id_academia', $modalidade->getIdAcademia(), PDO::PARAM_INT);
            $stmt->bindValue(':nome', $modalidade->getNome());
            $stmt->bindValue(':descricao', $modalidade->getDescricao());

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function listarPorAcademia(int $idAcademia): array {
        $sql = "SELECT * 
                FROM modalidade 
                WHERE id_academia = :id_academia
                ORDER BY id_modalidade DESC";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPorId(int $id, int $idAcademia): ?array {
        $sql = "SELECT * 
                FROM modalidade 
                WHERE id_modalidade = :id
                AND id_academia = :id_academia";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    public function atualizar(ModalidadeDTO $modalidade): bool {
        try {
            $sql = "UPDATE modalidade 
                    SET nome = :nome, 
                        descricao = :descricao
                    WHERE id_modalidade = :id
                    AND id_academia = :id_academia";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':nome', $modalidade->getNome());
            $stmt->bindValue(':descricao', $modalidade->getDescricao());
            $stmt->bindValue(':id', $modalidade->getIdModalidade(), PDO::PARAM_INT);
            $stmt->bindValue(':id_academia', $modalidade->getIdAcademia(), PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function excluir(int $id, int $idAcademia): bool {
        try {
            $sql = "DELETE FROM modalidade 
                    WHERE id_modalidade = :id
                    AND id_academia = :id_academia";

            $stmt = $this->conexao->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function buscarPorNome(string $termo, int $idAcademia): array {
        $sql = "SELECT * 
                FROM modalidade
                WHERE id_academia = :id_academia
                AND nome LIKE :termo
                ORDER BY id_modalidade DESC";

        $stmt = $this->conexao->prepare($sql);
        $stmt->bindValue(':id_academia', $idAcademia, PDO::PARAM_INT);
        $stmt->bindValue(':termo', '%' . $termo . '%');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}