<?php

namespace App\Model;

use App\Model\Model;

//require_once __DIR__ . DIRECTORY_SEPARATOR . "/Database.php";

class Movie extends Model
{
    //protected string $table = __DIR__ . DIRECTORY_SEPARATOR . "/../../../movies.json";
    public function genres() {
        // var_dump("estou aqui");die;
        return 'INNER JOIN filmes_generos on filmes_generos.id_filme = movies.id';
    }

    public function list($criteria): array
    {
        $conn = $this->getConnection();

        $genres = $criteria['genres'] ?? [];
        $name = $criteria['name'] ?? '';

        $resultadoIdFilm = [];
        $resultadoIdGenr = [];
        //array contendo todo os genero, pois há filmes que possui outros generos alem dos que os generos selecionados para o filtro
        $AllGenres = [];
        $result = [];

        $sqlMovies = 'SELECT BIN_TO_UUID(id) as id, name, description, image, year_publication, featured FROM movies where name LIKE :name';
        $sqlGenres = 'SELECT * FROM genres';
        $sqlMo_Gen = 'SELECT BIN_TO_UUID(id_filme) as id_filme, id_genero FROM filmes_generos';

        $sth = $conn->prepare($sqlMovies);
        $sth->bindValue(':name', "%$name%", \PDO::PARAM_STR);
        $sth->execute();
        $resultMovies = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $sth = $conn->prepare($sqlGenres);
        $sth->execute();
        $resultGenres = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $sth = $conn->prepare($sqlMo_Gen);
        $sth->execute();
        $resultRelacional = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($resultMovies as $linha) {
            $resultadoIdFilm[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                    'description' => $linha["description"],
                    'image' => $linha["image"],
                    'year_publication' => $linha["year_publication"] == 0000 ? "Sem dados" : $linha["year_publication"],
                    'featured' => $linha["featured"],
                ];
        }
        
        if(count($genres)>0){
            foreach ($resultGenres as $linha) {
                if(in_array($linha["name"],$genres)) $resultadoIdGenr[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                ];

                $AllGenres[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                ];
            }
        }else{
            foreach ($resultGenres as $linha) {
                $resultadoIdGenr[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                ];
                
                $AllGenres[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                ];
            }  
        } 
        foreach ($resultRelacional as $linha) {
            if(array_key_exists($linha['id_filme'],$result)){
                array_push($result[$linha['id_filme']]['genres'], $AllGenres[$linha["id_genero"]]["name"]);
            }else{
                if(array_key_exists($linha["id_genero"], $resultadoIdGenr) && array_key_exists($linha["id_filme"], $resultadoIdFilm)){
                    $result[$linha["id_filme"]] = [
                            'id' => $linha["id_filme"],
                            'name' => $resultadoIdFilm[$linha["id_filme"]]["name"],
                            'description' => $resultadoIdFilm[$linha["id_filme"]]["description"],
                            'image' => $resultadoIdFilm[$linha["id_filme"]]["image"],
                            'year_publication' => $resultadoIdFilm[$linha["id_filme"]]["year_publication"],
                            'featured' => $resultadoIdFilm[$linha["id_filme"]]["featured"],
                            'genres' => [$resultadoIdGenr[$linha["id_genero"]]["name"]]
                    ];
                }
            }
        }
        
        // foreach ($data as $movie) {
        //     // validar se tem os parâmetros para fazer os filtros
        //     $hasName = stripos($movie["name"], $name) !== false;
        //     $hasGenre = array_intersect($movie["genres"], $genres);
        //     if ($hasName && count($hasGenre) > 0) {
        //         $result[] = $movie;
        //     }
        // }

        //$sql = 'SELECT BIN_TO_UUID(id) as id, name, description, image, year_publication, featured, genero FROM view_name where name LIKE :name ';
        // $sth = $conn->prepare($sql);
        // $sth->bindValue(':name', "%$name%", \PDO::PARAM_STR);
        // $sth->execute();
        // $resultado = $sth->fetchAll(\PDO::FETCH_ASSOC);

        // foreach ($resultado as $linha) {
        //     if(array_key_exists($linha['id'],$result)){
        //         array_push($result[$linha['id']]['genres'], $linha["genero"]);

        //     }else{
        //         $result[$linha["id"]] = [
        //             'id' => $linha["id"],
        //             'name' => $linha["name"],
        //             'description' => $linha["description"],
        //             'image' => $linha["image"],
        //             'year_publication' => $linha["year_publication"],
        //             'featured' => $linha["featured"],
        //             'genres' => [$linha["genero"]]
        //         ];
        //     }
        //     if(empty(array_intersect($genres, $result[$linha["id"]]['genres']))){
        //         unset($result[$linha['id']]);
        //     }
        // }

        return $result;
    }

    public function list2($criteria) {
        $conn = $this->getConnection();
        $genres = $criteria['genres'] ?? [];

        $name = $criteria['name'] ?? '';

        // $innerJoinGenres = '';
        // $where = [];
        // $values = [];

        if ($name) {
            $this->where('movies.name', 'LIKE', "%$name%");
        }

        
        if (!empty($genres)) {
            $this->with(['genres' => function ($query) use ($genres) {
                $this->whereIn('id_genero', $genres);
            }]);

              //$this->whereIn('id_genero', $genres);
        }


        $where = [...$this->wheres, ...$this->where];
        $where = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $innerJoin = implode(' ', $this->innerJoin);
        
        $sqlMovies = <<<EOF
        SELECT DISTINCT(BIN_TO_UUID(id)) as id, name, description, image, year_publication, featured
        FROM movies 
        $innerJoin
        $where
        order by name
        EOF;
        //var_dump($sqlMovies);die;
        $sth = $conn->prepare($sqlMovies);
        $values = [...$this->values, ...$this->bindValues];
        //var_dump($this->bindValues);die;
        $sth->execute($values);
        
        $resultadoIdFilm = [];
        $idFilmes = [];
        foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $linha) {
            $idFilmes[] = $linha["id"];
            $resultadoIdFilm[$linha["id"]] = [
                    'id' => $linha["id"],
                    'name' => $linha["name"],
                    'description' => $linha["description"],
                    'image' => $linha["image"],
                    'year_publication' => $linha["year_publication"] == 0000 ? "Sem dados" : $linha["year_publication"],
                    'featured' => $linha["featured"],
                    'genres' => [],
                ];
        }


        if (count($idFilmes)) {
            $bindMovieIds = [];
            $values = [];
            
            foreach ($idFilmes as $idx => $filmeId) {
                $bindMovieIds[] = "UUID_TO_BIN(:filme$idx)";
                $values[":filme$idx"] = $filmeId;
            }
            $bindMovieIds = implode(',', $bindMovieIds);
            $sqlGenres = <<<EOF
            SELECT name, BIN_TO_UUID(id_filme) as id_filme
            FROM genres 
            INNER JOIN filmes_generos on genres.id = filmes_generos.id_genero
            WHERE filmes_generos.id_filme in ($bindMovieIds)
            order by name
            EOF;
            
            $sth = $conn->prepare($sqlGenres);
            $sth->execute($values);

            foreach ($sth->fetchAll(\PDO::FETCH_ASSOC) as $linha) {
                $resultadoIdFilm[$linha['id_filme']]['genres'][] = $linha['name'];
            }
        }



        return array_values($resultadoIdFilm);
    }

    public function insert($data){
        $conn = $this->getConnection();

        $insertFilm = 'INSERT INTO movies VALUES (UUID_TO_BIN(UUID()), :name, :description, :image, :year_publication, :featured)';
        $statement = $conn->prepare($insertFilm);
        $statement->bindValue('name', $data['name'], \PDO::PARAM_STR);
        $statement->bindValue('description', $data['description'], \PDO::PARAM_STR);
        $statement->bindValue('image', $data['image'], \PDO::PARAM_STR);
        $statement->bindValue('year_publication', $data['year_publication'], \PDO::PARAM_INT);
        $statement->bindValue('featured', $data['featured'], \PDO::PARAM_BOOL);
        $statement->execute();

        $sth = $conn->prepare("SELECT id FROM movies where name = :name");
        $sth->bindValue('name', $data['name'], \PDO::PARAM_STR);
        $sth->execute();
        $resultMovies = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($data["genres"] as $genero) {
            foreach($resultMovies as $movie){
                $relacional = "INSERT INTO filmes_generos VALUES (:id_film, :id_genr)";
                $sta = $conn->prepare($relacional);
                $sta->bindValue('id_film', $movie['id'],\PDO::PARAM_STR);
                $sta->bindValue('id_genr', $genero,\PDO::PARAM_STR);
                $sta->execute();
            }
            
        }

    }
}

// SELECT DISTINCT(BIN_TO_UUID(id)) as id, name, description, image, year_publication, featured 
// FROM movies 
// INNER JOIN filmes_generos on filmes_generos.id_filme = movies.id
// WHERE movies.name LIKE '%Poderoso%' 
// #AND WHERE filmes_generos.id_genero = UUID_TO_BIN('b12017d4-2c49-11f0-9d3a-3ae1d6662955')
// LIMIT 100
// https://stackoverflow.com/questions/28251144/inserting-and-selecting-uuids-as-binary16/