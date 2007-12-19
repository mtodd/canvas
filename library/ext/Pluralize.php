<?php
	// @title	Pluralize
	// @role	user-defined extension (used in helper)
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2006-01-08
	// @desc	Handles pluralizing words passed to it.
	// @refer_to "An Algorithmic Approach to English Pluralization":http://www.csse.monash.edu.au/~damian/papers/HTML/Plurals.html
	// @requires stdexception.php (StdException class)
	
	// inflector (handles pluralization and singularization)
	class Inflector {
		private static $words_that_do_not_inflect_in_the_plural = array("fish", "-ois", "sheep", "deer", "-pox", '[A-Z].*ese', "-itis"); // will return original word
		private static $user_defined_inflections = array(
			// "word"=>"inflection",
			"role"=>"roles",
/*			"comment"=>"comments",
			"user"=>"users",
			"word"=>"words",
			"category"=>"categories",
			"file"=>"files",
			"post"=>"posts",
			"tag"=>"tags",
			"role"=>"roles",
			"activity"=>"activities",
			"event"=>"events",
			"favorite"=>"favorites",
			"photo"=>"photos",
			"link"=>"links",
			"privilege"=>"privileges",
*/		); // defined by the user by define_inflection()
		private static $irregular_words = array(
			'beef'=>'beefs',
			'brother'=>'brothers',
			'child'=>'children',
			'person'=>'people',
			'cow'=>'cows',
			'ephemeris'=>'ephemerides',
			'genie'=>'genies',
			'money'=>'monies',
			'mongoose'=>'mongooses',
			'mythos'=>'mythoi',
			'octopus'=>'octopuses',
			'ox'=>'oxen',
			'soliloquy'=>'soliloquies',
			'trilby'=>'trilbys',
		);
		private static $irregular_inflections = array(
			'-man'=>'-men',
			'-[lm]ouse'=>'-ice',
			'-tooth'=>'-teeth',
			'-goose'=>'-geese',
			'-foot'=>'-feet',
			'-zoon'=>'-zoa',
			// '-[csx]is'=>'-es',
		);
		private static $classical_inflections = array(
			'-ex'=>'-ices',
			'-um'=>'-a',
			'-on'=>'-a',
			'-a'=>'-ae',
		);
		private static $es = array(
			'-ch'=>'-ches',
			'-sh'=>'-shes',
			'-ss'=>'-sses',
		);
		private static $f = array(
			'-f'=>'-ves',
		);
		private static $y = array(
			'-[aeiou]y'=>'-ys',
			'-[A-Z].*y'=>'-ys',
			'-y'=>'-ies',
		);
		private static $o = array(
			'-[aeiou]o'=>'-os',
			'-o'=>'-oes',
		);
		
		// pluralize the word
		public function pluralize($word) {
			// run the gamut
			if($inflection = self::run_gamut($word)) return $inflection;
			
			// if it ends in -s, pluralize it with -es
			if(substr($word, -1, 1) == 's') return "{$word}es";
			
			// otherwise, just add an -s to the word
			return "{$word}s";
		}
		public function singularize($word) {
			// run the gamut
			if($inflection = self::run_gamut($word)) return $inflection;
			
			// if it ends in -es, remove it and return
			if(substr($word, -2, 2) == 'es') return substr($word, 0, -2);
			
			// otherwise, if the word ends in -s, remove it and return it
			if(substr($word, -1, 1) == 's') return substr($word, 0, -1);
			return $word;
		}
		private static function run_gamut($word) {
			if($inflection = self::user_defined($word)) return $inflection;
			
			// return the word if it's the same plural or singular
			if(self::does_not_inflect($word)) return $word;
			
			// normally we'd handle  pronouns here, but I don't see any point in doing that for this,
			// but it could always be fleshed out in the future to include this functionality.
			
			// check for irregular words and inflections
			if($inflection = self::irregular($word)) return $inflection;
			
			// check for classical inflections
			if($inflection = self::classical($word)) return $inflection;
			
			// check for -es inflections
			if($inflection = self::es($word)) return $inflection;
			
			// check for -f inflections
			if($inflection = self::f($word)) return $inflection;
			
			// check for -y inflections
			if($inflection = self::y($word)) return $inflection;
			
			// check for -o inflections
			if($inflection = self::o($word)) return $inflection;
			
			// none of these, so return false to signify no change
			return false;
		}
		
		// set user defined inflections
		public static function define_inflection($inflection) {
			// @desc		alias for define_inflections($inflections)
			define_inflections($inflection);
		}
		public static function define_inflections($inflections) {
			// @desc		defines numerous inflections
			// @format		["word"=>"inflection", ...]
			foreach($inflections as $word=>$inflection) self::$user_defined_inflections[$word] = $inflection;
		}
		private static function user_defined($word) {
			// @desc		returns the inflected word if it's been defined by the user... false if not
			if(array_key_exists($word, self::$user_defined_inflections)) {
				return self::$user_defined_inflections[$word];
			}
			if(in_array($word, self::$user_defined_inflections)) {
				return array_search($word, self::$user_defined_inflections);
			}
			
			return false;
		}
		private static function does_not_inflect($word) {
			// check to see if a word does not inflect
			foreach(self::$words_that_do_not_inflect_in_the_plural as $noninflector) {
				if(substr($noninflector, 0, 1) == '-') $noninflector = '.*' . substr($noninflector, 1);
				if(preg_match("/{$noninflector}/", $word) == 1) {
					// print "Warning: noninflector detected ({$word})\n";
					return true; // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function irregular($word) {
			// @desc		returns irregular forms of words
			
			// check if it's an irregular word
			if(array_key_exists($word, self::$irregular_words)) {
				return self::$irregular_words[$word];
			}
			if(in_array($word, self::$irregular_words)) {
				return array_search($word, self::$irregular_words);
			}
			
			// if it hasn't matched yet, then check to see if it's an irregular inflection
			foreach(self::$irregular_inflections as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					$inflection_root = preg_replace('/(.*)(\[.*\])(.*)/', '$1$3', $inflection_root);
					$inflected_form = preg_replace('/(.*)(\[.*\])(.*)/', '$1$3', $inflected_form);
					// print "Warning: irregular inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$irregular_inflections as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					$inflection_root = preg_replace('/(.*)(\[.*\])(.*)/', '$1$3', $inflection_root);
					$inflected_form = preg_replace('/(.*)(\[.*\])(.*)/', '$1$3', $inflected_form);
					// print "Warning: irregular inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function classical($word) {
			// check to see if it's a classical inflection
			foreach(self::$classical_inflections as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: classical inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$classical_inflections as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: classical inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function es($word) {
			// @desc		returns the inflection of an -es inflected/inflectable word
			// check to see if it's an -es inflection
			foreach(self::$es as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -es inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$es as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -es inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function f($word) {
			// @desc		returns the inflection of an -f inflected/inflectable word
			// check to see if it's an -f inflection
			foreach(self::$f as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -f inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$f as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -f inflection detected ({$word})\n";
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function y($word) {
			// @desc		returns the inflection of a -y inflected/inflectable word
			// check to see if it's a -y inflection
			foreach(self::$y as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -y inflection detected ({$word})\n";
					return self::inflect($word, $inflection_root, $inflected_form);
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$y as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -y inflection detected ({$word})\n";
					return self::inflect($word, $inflection_root, $inflected_form);
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			
			return false;
		}
		private static function o($word) {
			// @desc		returns the inflection of an -o inflected/inflectable word
			// check to see if it's an -o inflection
			foreach(self::$o as $inflection=>$inflected_form) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -o inflection detected ({$word})\n";
					return self::inflect($word, $inflection_root, $inflected_form);
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			// now for singular form
			foreach(self::$o as $inflected_form=>$inflection) {
				$inflection_root = substr($inflection, 1);
				$inflection = ".*{$inflection_root}";
				$inflected_form = substr($inflected_form, 1);
				if(preg_match("/{$inflection}$/", $word) == 1) {
					// print "Warning: -o inflection detected ({$word})\n";
					return self::inflect($word, $inflection_root, $inflected_form);
					return str_replace($inflection_root, $inflected_form, $word); // if the word matches the regex (once), then return the word
				}
			}
			return false;
		}
		
		// action functions
		private static function inflect($word, $ending, $inflection) {
			$ending = str_replace('.*', '', str_replace('[A-Z]', '', str_replace('[aeiou]', '', $ending)));
			$inflection = str_replace('.*', '', str_replace('[A-Z]', '', str_replace('[aeiou]', '', $inflection)));
			// $ending = preg_replace('/(.*)(\[\w*\]|\W*)(.*)$/', '$1$3', $ending);
			// $inflection = preg_replace('/(.*)(\[\w*\]|\W*)(.*)$/', '$1$3', $inflection);
			return preg_replace("/(\w+){$ending}$/", '$1' . $inflection, $word);
		}
		
		// get pluralization
		public static function __get($word) {
			return self::pluralize($word);
		}
		public static function __set($word, $number) {
			if($number > 1) return self::pluralize($word);
			return $word;
		}
	}
	
	class PluralizeException extends StdException {}
	
	// stolen from Rails' active_support
	/*# The Inflector transforms words from singular to plural, class names to table names, modularized class names to ones without,
	# and class names to foreign keys.
	module Inflector 
	  extend self

	  def pluralize(word)
	    result = word.to_s.dup

	    if uncountable_words.include?(result.downcase)
	      result
	    else
	      plural_rules.each { |(rule, replacement)| break if result.gsub!(rule, replacement) }
	      result
	    end
	  end

	  def singularize(word)
	    result = word.to_s.dup

	    if uncountable_words.include?(result.downcase)
	      result
	    else
	      singular_rules.each { |(rule, replacement)| break if result.gsub!(rule, replacement) }
	      result
	    end
	  end

	  def camelize(lower_case_and_underscored_word)
	    lower_case_and_underscored_word.to_s.gsub(/\/(.?)/) { "::" + $1.upcase }.gsub(/(^|_)(.)/) { $2.upcase }
	  end

	  def underscore(camel_cased_word)
	    camel_cased_word.to_s.gsub(/::/, '/').gsub(/([A-Z]+)([A-Z])/,'\1_\2').gsub(/([a-z\d])([A-Z])/,'\1_\2').downcase
	  end

	  def humanize(lower_case_and_underscored_word)
	    lower_case_and_underscored_word.to_s.gsub(/_/, " ").capitalize
	  end

	  def demodulize(class_name_in_module)
	    class_name_in_module.to_s.gsub(/^.*::/, '')
	  end

	  def tableize(class_name)
	    pluralize(underscore(class_name))
	  end

	  def classify(table_name)
	    camelize(singularize(table_name))
	  end

	  def foreign_key(class_name, separate_class_name_and_id_with_underscore = true)
	    Inflector.underscore(Inflector.demodulize(class_name)) + 
	      (separate_class_name_and_id_with_underscore ? "_id" : "id")
	  end

	  def constantize(camel_cased_word)
	    camel_cased_word.split("::").inject(Object) do |final_type, part| 
	      final_type = final_type.const_get(part)
	    end
	  end

	  private
	    def uncountable_words #:doc
	      %w( equipment information rice money species series fish )
	    end

	    def plural_rules #:doc:
	      [
	      	[/^(ox)$/i, '\1\2en'],		             # ox
	      	[/([m|l])ouse$/i, '\1ice'],	           # mouse, louse
	      	[/(matr|vert)ix|ex$/i, '\1ices'],      # matrix, vertex, index
	        [/(x|ch|ss|sh)$/i, '\1es'],            # search, switch, fix, box, process, address
	        [/([^aeiouy]|qu)ies$/i, '\1y'],
	        [/([^aeiouy]|qu)y$/i, '\1ies'],        # query, ability, agency
	        [/(hive)$/i, '\1s'],                   # archive, hive
	        [/(?:([^f])fe|([lr])f)$/i, '\1\2ves'], # half, safe, wife
	        [/sis$/i, 'ses'],                      # basis, diagnosis
	        [/([ti])um$/i, '\1a'],                 # datum, medium
	        [/(p)erson$/i, '\1eople'],             # person, salesperson
	        [/(m)an$/i, '\1en'],                   # man, woman, spokesman
	        [/(c)hild$/i, '\1hildren'],            # child
	      	[/(buffal|tomat)o$/i, '\1\2oes'],		   # buffalo, tomato
	      	[/(bu)s$/i, '\1\2ses'],	               # bus
	        [/(alias)/i, '\1es'],                  # alias
	      	[/(octop|vir)us$/i, '\1i'],            # octopus, virus - virus has no defined plural (according to Latin/dictionary.com), but viri is better than viruses/viruss
	      	[/(ax|cri|test)is$/i, '\1es'],         # axis, crisis  
	        [/s$/i, 's'],                          # no change (compatibility)
	        [/$/, 's']
	      ]
	    end

	    def singular_rules #:doc:
	      [
	        [/(matr)ices$/i, '\1ix'],
	      	[/(vert)ices$/i, '\1ex'],
	      	[/^(ox)en/i, '\1'],
	      	[/(alias)es$/i, '\1'],
	      	[/([octop|vir])i$/i, '\1us'],
	      	[/(cris|ax|test)es$/i, '\1is'],
	      	[/(shoe)s$/i, '\1'],
	      	[/(o)es$/i, '\1'],
	      	[/(bus)es$/i, '\1'],
	      	[/([m|l])ice$/i, '\1ouse'],
	        [/(x|ch|ss|sh)es$/i, '\1'],
	        [/(m)ovies$/i, '\1\2ovie'],
	        [/(s)eries$/i, '\1\2eries'],
	        [/([^aeiouy]|qu)ies$/i, '\1y'],
	        [/([lr])ves$/i, '\1f'],
	        [/(tive)s$/i, '\1'],
	        [/(hive)s$/i, '\1'],
	        [/([^f])ves$/i, '\1fe'],
	        [/(^analy)ses$/i, '\1sis'],
	        [/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i, '\1\2sis'],
	        [/([ti])a$/i, '\1um'],
	        [/(p)eople$/i, '\1\2erson'],
	        [/(m)en$/i, '\1an'],
	        [/(s)tatus$/i, '\1\2tatus'],
	        [/(c)hildren$/i, '\1\2hild'],
	        [/(n)ews$/i, '\1\2ews'],
	        [/s$/i, '']
	      ]
	    end
	end*/
?>