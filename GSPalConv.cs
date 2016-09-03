using System;
using System.IO;

namespace GSPalConv
{
	class Program
	{
		static void Main(string[] args)
		{
			BinaryWriter bw;
			BinaryReader br;
			
			//Arguments stuff
			if (args.Length == 0)
			{
				System.Console.WriteLine("Genesis Standard Palette Converter: converts 0BGR palette data into RRGGBB data \n Perfect for YY-CHR! \n Gens/Genecyst/Dgen savestate autodetection included.");
				System.Console.WriteLine("Usage: GSPalConv <filename> [offset = 0 (give a hex value without a prefix)] [lines = 1] [output = filename.pal]");
				return;
			}
			
			//Set up the datum
			string infile;
			string outfile;
			int offset;
			int lines;
			bool test = false;
			bool endian = false;
			byte red;
			byte green;
			byte blue;
			byte color1;
			byte color2;
			
			//Input file
			infile = args[0];
			
			//Output file
			if (args.Length >= 4)
			{
				outfile = args[3];
			}
			else
			{
				string[] words = args[0].Split('.');
				outfile = words[0] + ".pal";
			}
			
			//Palette data offset (why do some games put this data in different places? Default is for working with disassembly files)
			if (args.Length >= 2)
			{
				offset = Int32.Parse(args[2], System.Globalization.NumberStyles.HexNumber);
			}
			else
			{
				offset = 0;
			}
			
			//Make sure this argument is a number...
			if (args.Length >= 3)
			{
				test = int.TryParse(args[3], out lines);
			}
			else
			{
				lines = 1;
			}
			
			//And if so...
			if (test == true)
			{
				//Minimum of one palette line (like Sonic's palette from the disassembly)
				if (lines < 1)
				{
					lines = 1;
				}
				//Maximum of 16 lines (YY-CHR can't handle more than that!)
				else if (lines > 16)
				{
					lines = 16;
				}
			}
			else
			{
				lines = 1;
			}
			
			//reading from the file
			try
			{
				br = new BinaryReader(new FileStream(infile, FileMode.Open));
			}
			catch (IOException e)
			{
				Console.WriteLine(e.Message + "\n Cannot open input file.");
				return;
			}
			
			//If we weren't given any other parameters aside from filename, let's check to see if this is a savestate.
			if(args.Length == 1)
			{
				//At the start of the file, there is a three-char magic: GST
				char[] magic = br.ReadChars(3);
				string magics = new string(magic);
				if (magics == "GST")
				{
					Console.WriteLine("Detected savestate format: " + magics);
					lines = 4;
					offset = Int32.Parse("112", System.Globalization.NumberStyles.HexNumber);
					endian = true;
				}
				//Please be kind! Rewind.
				br.BaseStream.Seek(0, SeekOrigin.Begin);
			}
			
			
			br.ReadBytes(offset);
			
			//create the output file
			try
			{
				bw = new BinaryWriter(new FileStream(outfile, FileMode.Create));
			}
			catch (IOException e)
			{
				Console.WriteLine(e.Message + "\n Cannot create output file.");
				return;
			}
			
			//The main event!
			for (int i = 0; i < (lines * 16); i++)
			{
				if (!endian)
				{
					// Genesis pallete entries are words - 0B GR
					color1 = br.ReadByte();
					color2 = br.ReadByte();
				}
				else
				{
					//But GST files store them as GR 0B. ENDIANS!!!
					color2 = br.ReadByte();
					color1 = br.ReadByte();
				}
				
				red = Convert.ToByte(17 * (color2 % 16));
				green = Convert.ToByte(17 * Math.Floor(color2 / 16f));
				blue = Convert.ToByte(17 * (color1 % 16));
				
				//writing into the file
				try
				{
					bw.Write(red);
					bw.Write(green);
					bw.Write(blue);
				}
				catch (IOException e)
				{
					Console.WriteLine(e.Message + "\n Cannot write to output file.");
					return;
				}
			}
			
			//Close up everything and let user know a success was happen
			br.Close();
			bw.Close();
			Console.WriteLine("SUCCESS!");
			return;
		}
	}
}