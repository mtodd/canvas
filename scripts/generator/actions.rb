class Generator
  # generate controller
  def generate_controller
    # template properties
    @controller_name = @args.shift
    @actions = @args
    @title = 'Controller'
    @desc = 'Describe functionality here...'
    @author = `whoami`.sub("\n", '')
    
    # filename to write to
    file = 'controllers/' + @controller_name + '_controller.php'
    
    # generate and write template
    generate 'controller', file
    
    # tell user that it was created
    print "Controller created (in " + file + ")"
  end
  
  # generate model
  def generate_model
    # template properties
    @model_name = @args.shift
    @title = 'Model'
    @desc = 'Describe functionality here...'
    @author = `whoami`.sub("\n", '')
    
    # filename to write to
    file = 'models/' + @model_name + '.php'
    
    # generate and write template
    generate 'model', file
    
    # tell user that it was created
    print "Model created (in " + file + ")"
  end
  
  # generate view
  def generate_view
    # template properties
    @view_name = @args.shift
    @actions = @args
    
    files = []
    # filenames to write to
    @actions.each do |action|
      files[files.length] = 'views/' + @view_name + '/' + action + '.php'
    end
    
    # create views directory if it does not exist
    Dir.mkdir('views/' + @view_name) if (!(File.exists? 'views/' + @view_name) and !(File.directory? 'views/' + @view_name))
    
    # generate and write template
    generate 'view_layout', 'views/' + @view_name + '/layout.php'
    generate 'view', files
    
    # create 'compile', 'cache', and 'config' smarty directories
    dirs = ['compile', 'config', 'cache']
    dirs.each do |dir|
      dir = 'views/' + @view_name + '/' + dir
      Dir.mkdir(dir) if (!(File.exists? dir) and !(File.directory? dir))
    end
    # fix permissions
    `chmod -v a+rwx views/*/compile views/*/config views/*/cache`
    
    # tell user that it was created
    print "Views created (in " + File.dirname(files[0]) + ")"
  end
end