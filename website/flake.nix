{
  description = "Personal Blog Laravel development environment";

  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";

  outputs = { self, nixpkgs }:
    let
      system = "x86_64-linux";
      pkgs = import nixpkgs { inherit system; };

      phpWithRedis = pkgs.php.buildEnv {
        extensions = { enabled, all }:
          enabled ++ [ all.redis ];
      };
    in
    {
      devShells.${system}.default = pkgs.mkShell {
        packages = [
          phpWithRedis
          phpWithRedis.packages.composer
          pkgs.redis
        ];
      };
    };
}
