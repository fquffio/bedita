# -*- mode: ruby -*-
# vi: set ft=ruby :

hostname = "bedita4"
name = "BEdita 4"

cpus = 2
memory = 2048

Vagrant.configure(2) do |config|
  config.vm.box = "ubuntu/trusty64"

  # Set up machine network and hostname:
  config.vm.hostname = "#{hostname}.bedita.local"
  config.vm.network "private_network", ip: "10.0.83.4"

  # Add additional shared folder to easen Apache setup:
  config.vm.synced_folder ".", "/var/www/html", owner: "vagrant", group: "www-data", mount_options: ["dmode=775,fmode=764"]
  config.vm.synced_folder "provision/", "/vagrant"

  # Configure VirtualBox provider:
  config.vm.provider "virtualbox" do |vb|
    vb.name = name
    vb.customize [
      "modifyvm", :id,
      "--groups", "/Vagrant"
    ]

    vb.cpus = cpus
    vb.memory = memory
  end

  # Start Docker containers
  config.vm.provision :shell, inline: 'if [[ -x `which docker` ]] && [[ -n `docker ps -a -q` ]]; then docker rm -fv $(docker ps -a -q); fi'
  config.vm.provision :docker do |docker|
    docker.pull_images "mysql:5.6"
    docker.run "mysql-5.6",
      image: "mysql:5.6",
      args: "-e MYSQL_ROOT_PASSWORD=root -e MYSQL_USER=bedita -e MYSQL_PASSWORD=bedita -e MYSQL_DATABASE=bedita_test -p 127.0.0.1:33060:3306"

    docker.pull_images "mysql:5.7"
    docker.run "mysql-5.7",
      image: "mysql:5.7",
      args: "-e MYSQL_ROOT_PASSWORD=root -e MYSQL_USER=bedita -e MYSQL_PASSWORD=bedita -e MYSQL_DATABASE=bedita_test -p 127.0.0.1:33070:3306"

    docker.pull_images "postgres"
    docker.run "postgres",
      args: "-e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=bedita_test -p 127.0.0.1:5432:5432"

    docker.pull_images "redis"
    docker.run "redis",
      args: "-p 127.0.0.1:6379:6379"

    docker.pull_images "chialab/elasticsearch:manager"
    docker.run "elasticsearch",
      image: "chialab/elasticsearch:manager",
      args: "-p 9200:9200"

    docker.pull_images "kibana"
    docker.run "kibana",
      args: "-p 5601:5601"
  end

  # Install Ansible via PIP and Git via APT:
  config.vm.provision "shell", path: "provision/install_ansible.sh"

  # Workaround for mitchellh/vagrant#6793 (see https://github.com/mitchellh/vagrant/issues/6793#issuecomment-172408346):
  config.vm.provision "shell" do |s|
    s.inline = '[[ ! -f $1 ]] || grep -F -q "$2" $1 || sed -i "/__main__/a \\    $2" $1'
    s.args = ['/usr/local/bin/ansible-galaxy', "if sys.argv == ['/usr/local/bin/ansible-galaxy', '--help']: sys.argv.insert(1, 'info')"]
  end

  # Ansible provisioning:
  config.vm.provision :ansible_local do |ansible|
    ansible.galaxy_role_file = "requirements.yml"
    ansible.galaxy_roles_path = "roles"

    ansible.playbook = "playbook.yml"

    if File.exists?("vars/vagrant.yml")
      ansible.extra_vars = "vars/vagrant.yml"
    end
  end
end
