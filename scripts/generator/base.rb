class Generator
  def initialize(arguments)
    @args = arguments
  end
  
  # generate and write template
  def generate template, file
    if file.kind_of? String then # one file
      write_template(file, ERB.new(load_template(template)).result(binding))
    else # multiple files
      file.each do |f|
        write_template(f, ERB.new(load_template(template)).result(binding))
      end
    end
  end
  
  # load template
  def load_template(template)
    file = File.dirname(__FILE__) + '/templates/' + template + '.rhtml'
    return IO.readlines(file).join if File.file? file
  end
  
  # write template
  def write_template(file, template)
    f = File.new(file, File::CREAT|File::RDWR, 0755)
    f << template
    f.close
  end
end